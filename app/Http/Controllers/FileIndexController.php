<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\FileIndexing;
use App\Models\ApplicationMother;
use App\Models\IndexedFileTracker;
use App\Models\FileindexingBatch;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Services\FileIndexingBatchService;
use RuntimeException;

class FileIndexController extends Controller
{
    private const MAX_BATCH_ENTRIES = FileIndexingBatchService::BATCH_CAPACITY;
    private const MAX_DUPLICATE_RECORDS = 500;
    private const CLEAN_CSV_CACHE_PREFIX = 'fileindexing_clean_csv_';
    private const CLEAN_CSV_TTL_MINUTES = 30;
    private const CLEAN_CSV_STORAGE_DIR = 'fileindexing/clean-previews';
    private const SQL_SERVER_PARAM_LIMIT = 2000; // Safe limit under SQL Server's 2100 parameter limit

    protected FileIndexingBatchService $batchService;

    public function __construct(FileIndexingBatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    /**
     * Execute a whereIn query with chunking to avoid SQL Server's parameter limit
     */
    private function chunkedWhereIn($query, $column, $values, $chunkSize = null)
    {
        if (empty($values)) {
            return collect();
        }

        $chunkSize = $chunkSize ?? self::SQL_SERVER_PARAM_LIMIT;
        $chunks = array_chunk($values, $chunkSize);
        $results = collect();

        foreach ($chunks as $chunk) {
            $chunkResults = (clone $query)->whereIn($column, $chunk)->get();
            $results = $results->merge($chunkResults);
        }

        return $results;
    }

    /**
     * Display the file indexing dashboard
     */
    public function index()
    {
        try {
            $PageTitle = 'File Indexing';
            $PageDescription = 'Digital File Index Management System';
            
            // Get statistics for dashboard
            $stats = [
                'pending_files' => $this->getPendingFilesCount(),
                'indexed_today' => $this->getIndexedTodayCount(),
                'total_indexed' => FileIndexing::on('sqlsrv')
                    ->where(function ($query) {
                        $query->where('is_deleted', 0)
                              ->orWhereNull('is_deleted');
                    })
                    ->count(),
            ];
            
            // Get recent file indexing records
            $recentIndexes = FileIndexing::on('sqlsrv')
                ->with(['mainApplication', 'scannings', 'pagetypings'])
                ->where(function ($query) {
                    $query->where('is_deleted', 0)
                          ->orWhereNull('is_deleted');
                })
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            return view('fileindexing.index', compact('PageTitle', 'PageDescription', 'stats', 'recentIndexes'));
        } catch (Exception $e) {
            Log::error('Error loading file indexing dashboard', [
                'error' => $e->getMessage()
            ]);
            
            return view('fileindexing.index', [
                'PageTitle' => 'File Indexing',
                'PageDescription' => 'Digital File Index Management System',
                'stats' => ['pending_files' => 0, 'indexed_today' => 0, 'total_indexed' => 0],
                'recentIndexes' => collect()
            ]);
        }
    }

    /**
     * Store a newly created file index
     */
    public function store(Request $request)
    {
        try {
            // Handle bulk entries from scanning interface
            if ($request->has('bulk_entries')) {
                return $this->storeBulkEntries($request);
            }

            // Handle single entry creation with smart file number selector
            $validator = Validator::make($request->all(), [
                'main_application_id' => 'nullable|integer',
                'subapplication_id' => 'nullable|integer',
                'file_number' => 'required|string|max:255',
                'file_number_id' => 'nullable|integer', // ID from fileNumber table when selected
                'file_title' => 'required|string|max:255',
                'st_fillno' => 'nullable|string|max:100',
                'serial_no' => 'nullable|string|max:100',
                'batch_no' => 'nullable|string|max:100',
                'batch_id' => 'nullable|string|max:100',
                'shelf_label_id' => 'nullable|string|max:100',
                'tracking_id' => 'nullable|string|max:100',
                'shelf_location' => 'nullable|string|max:100',
                'land_use_type' => 'nullable|string|max:100',
                'plot_number' => 'nullable|string|max:100',
                'tp_no' => 'nullable|string|max:255',
                'lpkn_no' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'district' => 'nullable|string|max:100',
                'lga' => 'nullable|string|max:100',
                'has_cofo' => 'boolean',
                'is_merged' => 'boolean',
                'has_transaction' => 'boolean',
                'is_problematic' => 'boolean',
                'is_co_owned_plot' => 'boolean',
                'source' => 'nullable|string',
                'scanning_id' => 'nullable|integer',
                'extracted_metadata' => 'nullable|array',
                'registry' => 'nullable|string|max:255',
                // Smart file number selector fields
                'source_file_id' => 'nullable|string',
                'file_number_source' => 'nullable|in:existing,manual',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Log incoming registry and payload for debugging
            Log::info('FileIndexController::store - validated payload', [
                'registry' => $validated['registry'] ?? null,
                'tp_no' => $validated['tp_no'] ?? null,
                'location' => $validated['location'] ?? null,
                'payload' => $validated,
                'user_id' => Auth::id()
            ]);

            // Process file number ID from smart selector
            $fileNumberId = $this->processFileNumberId($validated);

            // Check for existing file indexing
            $existingIndex = $this->checkForExistingFileIndex($validated, $fileNumberId);
            
            if ($existingIndex) {
                return response()->json([
                    'success' => false,
                    'message' => 'File indexing already exists for this file number',
                    'redirect' => route('fileindexing.show', $existingIndex->id)
                ], 409);
            }

            // Create file indexing record
            $fileIndexingData = [
                'main_application_id' => $validated['main_application_id'] ?? null,
                'subapplication_id' => $validated['subapplication_id'] ?? null,
                'file_number' => $validated['file_number'],
                'file_number_id' => $fileNumberId,
                'file_title' => $validated['file_title'],
                'st_fillno' => $validated['st_fillno'] ?? null,
                'tracking_id' => $validated['tracking_id'] ?? $this->generateTrackingId(),
                'land_use_type' => $validated['land_use_type'] ?? 'Residential',
                'plot_number' => $validated['plot_number'],
                'tp_no' => $validated['tp_no'] ?? null,
                'lpkn_no' => $validated['lpkn_no'] ?? null,
                'location' => $validated['location'] ?? null,
                'district' => $validated['district'],
                'registry' => $validated['registry'] ?? null,
                'lga' => $validated['lga'] ?? 'Municipal',
                'has_cofo' => $validated['has_cofo'] ?? false,
                'is_merged' => $validated['is_merged'] ?? false,
                'has_transaction' => $validated['has_transaction'] ?? false,
                'is_problematic' => $validated['is_problematic'] ?? false,
                'is_co_owned_plot' => $validated['is_co_owned_plot'] ?? false,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                // Include batch fields when provided from frontend (auto-assignment preview)
                'batch_no' => $validated['batch_no'] ?? null,
                'serial_no' => $validated['serial_no'] ?? null,
                'shelf_location' => $validated['shelf_location'] ?? null,
                'shelf_label_id' => $validated['shelf_label_id'] ?? null,
                'batch_id' => $validated['batch_id'] ?? null,
            ];
            $assignment = [];

            try {
                [$fileIndexing, $assignment] = $this->createFileIndexingWithBatch($fileIndexingData);
            } catch (RuntimeException $runtimeException) {
                Log::warning('Batch assignment failed for file indexing create', [
                    'error' => $runtimeException->getMessage(),
                    'user_id' => Auth::id(),
                    'file_number' => $validated['file_number'] ?? null,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No active batch available. Please replenish shelf labels before creating new records.',
                ], 409);
            }

            $fileIndexing = $fileIndexing ?? null;

            // Refresh and log created model to ensure registry persisted
            try {
                $fileIndexing->refresh();
                Log::info('FileIndexController::store - created file_indexing', [
                    'id' => $fileIndexing->id,
                    'registry' => $fileIndexing->registry ?? null,
                    'file_number' => $fileIndexing->file_number,
                    'tp_no_saved' => $fileIndexing->tp_no,
                    'location_saved' => $fileIndexing->location,
                    'plot_number_saved' => $fileIndexing->plot_number,
                ]);
            } catch (\Throwable $e) {
                Log::warning('FileIndexController::store - refresh failed: ' . $e->getMessage());
            }

            Log::info('File indexing created via smart selector', [
                'file_indexing_id' => $fileIndexing->id,
                'file_number' => $fileIndexing->file_number,
                'file_number_id' => $fileNumberId,
                'file_title' => $fileIndexing->file_title,
                'file_number_source' => $validated['file_number_source'] ?? 'unknown',
                'source_file_id' => $validated['source_file_id'] ?? null,
                'created_by' => Auth::id(),
                'batch_assignment' => $assignment,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File indexing created successfully!',
                'file_indexing_id' => $fileIndexing->id,
                'redirect' => route('fileindexing.index'),
                'batch_assignment' => $assignment,
                'batch_info' => [
                    'batch_no' => $fileIndexing->batch_no,
                    'serial_no' => $fileIndexing->serial_no,
                    'shelf_location' => $fileIndexing->shelf_location,
                    'batch_id' => $fileIndexing->batch_id,
                    'shelf_label_id' => $fileIndexing->shelf_label_id,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Error creating file indexing via smart selector', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating file indexing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process file number ID from smart selector
     */
    private function processFileNumberId(array $validated)
    {
        // If file number was selected from dropdown, extract the ID
        if (!empty($validated['source_file_id']) && $validated['file_number_source'] === 'existing') {
            // Remove 'manual_' prefix if it exists (for manual entries)
            $sourceFileId = str_replace('manual_', '', $validated['source_file_id']);
            
            if (is_numeric($sourceFileId)) {
                return (int) $sourceFileId;
            }
        }

        // For manual entries, file_number_id remains null
        return null;
    }

    /**
     * Check for existing file indexing
     */
    private function checkForExistingFileIndex(array $validated, $fileNumberId = null)
    {
        // Check by file_number_id first if available (for selected files)
        if ($fileNumberId) {
            $existing = FileIndexing::on('sqlsrv')
                ->where('file_number_id', $fileNumberId)
                ->first();
            if ($existing) return $existing;
        }

        // Check by exact file number match
        $existing = FileIndexing::on('sqlsrv')
            ->where('file_number', $validated['file_number'])
            ->first();
        if ($existing) return $existing;

        // Check by application IDs if provided
        if (!empty($validated['main_application_id'])) {
            $existing = FileIndexing::on('sqlsrv')
                ->where('main_application_id', $validated['main_application_id'])
                ->first();
            if ($existing) return $existing;
        }

        if (!empty($validated['subapplication_id'])) {
            $existing = FileIndexing::on('sqlsrv')
                ->where('subapplication_id', $validated['subapplication_id'])
                ->first();
            if ($existing) return $existing;
        }

        return null;
    }

    /**
     * Store bulk file indexing entries from scanning interface
     */
    private function storeBulkEntries(Request $request)
    {
        try {
            // Check if this is AI indexing based on source field
            $entries = $request->input('bulk_entries', []);
            $isAiIndexing = !empty($entries) && 
                           collect($entries)->every(function($entry) {
                               return ($entry['source'] ?? '') === 'AI_Indexing';
                           });

            if ($isAiIndexing) {
                return $this->storeAiIndexedBulkEntries($request);
            }

            $validator = Validator::make($request->all(), [
                'bulk_entries' => 'required|array|min:1',
                'bulk_entries.*.scanning_id' => 'required|integer',
                'bulk_entries.*.file_number' => 'required|string|max:255',
                'bulk_entries.*.file_title' => 'required|string|max:255',
                'bulk_entries.*.plot_number' => 'nullable|string|max:100',
                'bulk_entries.*.tp_no' => 'nullable|string|max:255',
                'bulk_entries.*.location' => 'nullable|string|max:255',
                'bulk_entries.*.land_use_type' => 'nullable|string|max:100',
                'bulk_entries.*.district' => 'nullable|string|max:100',
                'bulk_entries.*.source' => 'nullable|string',
                'bulk_entries.*.application_id' => 'nullable|integer',
                'bulk_entries.*.source_table' => 'nullable|string|in:mother,sub',
                'bulk_entries.*.extracted_metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $entries = $request->input('bulk_entries');
            $createdCount = 0;
            $errors = [];

            foreach ($entries as $entry) {
                try {
                    [$fileIndexing, $assignment] = $this->createFileIndexingWithBatch([
                        'file_number' => $entry['file_number'],
                        'file_title' => $entry['file_title'],
                        'plot_number' => $entry['plot_number'] ?? null,
                        'tp_no' => $entry['tp_no'] ?? null,
                        'location' => $entry['location'] ?? null,
                        'land_use_type' => $entry['land_use_type'] ?? 'Residential',
                        'district' => $entry['district'] ?? null,
                        'lga' => null,
                        'has_cofo' => false,
                        'is_merged' => false,
                        'has_transaction' => false,
                        'is_problematic' => false,
                        'is_co_owned_plot' => false,
                        'main_application_id' => ($entry['source_table'] ?? null) === 'mother' ? ($entry['application_id'] ?? null) : null,
                        'subapplication_id' => ($entry['source_table'] ?? null) === 'sub' ? ($entry['application_id'] ?? null) : null,
                        'tracking_id' => $this->generateTrackingId(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);

                    $createdCount++;

                    Log::info('Bulk file indexing created', [
                        'file_indexing_id' => $fileIndexing->id,
                        'file_number' => $fileIndexing->file_number,
                        'scanning_id' => $entry['scanning_id'],
                        'source' => $entry['source'] ?? 'bulk_scanning_upload',
                        'created_by' => Auth::id(),
                        'batch_assignment' => $assignment,
                    ]);

                } catch (RuntimeException $runtimeException) {
                    $errors[] = "No active batch available while processing {$entry['file_title']}";
                    Log::warning('Bulk file indexing batch assignment failed', [
                        'entry' => $entry,
                        'error' => $runtimeException->getMessage(),
                    ]);
                } catch (Exception $e) {
                    $errors[] = "Error creating entry for {$entry['file_title']}: " . $e->getMessage();
                    Log::error('Error creating bulk file indexing entry', [
                        'entry' => $entry,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => $createdCount > 0,
                'message' => "Successfully created {$createdCount} file indexing entries!",
                'created_count' => $createdCount,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            Log::error('Error creating bulk file indexing entries', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating bulk file indexing entries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store AI-processed bulk entries (no scanning_id required)
     */
    private function storeAiIndexedBulkEntries(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bulk_entries' => 'required|array|min:1',
                'bulk_entries.*.file_number' => 'required|string|max:255',
                'bulk_entries.*.file_title' => 'required|string|max:255',
                'bulk_entries.*.plot_number' => 'nullable|string|max:100',
                'bulk_entries.*.tp_no' => 'nullable|string|max:255',
                'bulk_entries.*.location' => 'nullable|string|max:255',
                'bulk_entries.*.land_use_type' => 'nullable|string|max:100',
                'bulk_entries.*.district' => 'nullable|string|max:100',
                'bulk_entries.*.source' => 'nullable|string',
                'bulk_entries.*.application_id' => 'nullable|integer',
                'bulk_entries.*.source_table' => 'nullable|string|in:mother,sub',
                'bulk_entries.*.extracted_metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $entries = $request->input('bulk_entries');
            $createdCount = 0;
            $errors = [];
            $createdFiles = [];

            foreach ($entries as $entry) {
                try {
                    // Check if this file number already exists
                    $existingFile = FileIndexing::on('sqlsrv')
                        ->where('file_number', $entry['file_number'])
                        ->first();

                    if ($existingFile) {
                        $errors[] = "File number {$entry['file_number']} already exists";
                        continue;
                    }

                    [$fileIndexing, $assignment] = $this->createFileIndexingWithBatch([
                        'file_number' => $entry['file_number'],
                        'file_title' => $entry['file_title'],
                        'plot_number' => $entry['plot_number'] ?? null,
                        'tp_no' => $entry['tp_no'] ?? null,
                        'location' => $entry['location'] ?? null,
                        'land_use_type' => $entry['land_use_type'] ?? 'Residential',
                        'district' => $entry['district'] ?? null,
                        'lga' => null,
                        'has_cofo' => false,
                        'is_merged' => false,
                        'has_transaction' => false,
                        'is_problematic' => false,
                        'is_co_owned_plot' => false,
                        'main_application_id' => ($entry['source_table'] ?? null) === 'mother' ? ($entry['application_id'] ?? null) : null,
                        'subapplication_id' => ($entry['source_table'] ?? null) === 'sub' ? ($entry['application_id'] ?? null) : null,
                        'tracking_id' => $this->generateTrackingId(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                        'workflow_status' => 'ai_indexed',
                    ]);

                    $createdCount++;
                    $createdFiles[] = [
                        'id' => $fileIndexing->id,
                        'file_number' => $fileIndexing->file_number,
                        'file_title' => $fileIndexing->file_title
                    ];

                    Log::info('AI-indexed file created', [
                        'file_indexing_id' => $fileIndexing->id,
                        'file_number' => $fileIndexing->file_number,
                        'source' => $entry['source'] ?? 'AI_Indexing',
                        'extracted_metadata' => $entry['extracted_metadata'] ?? null,
                        'created_by' => Auth::id(),
                        'batch_assignment' => $assignment,
                    ]);

                } catch (RuntimeException $runtimeException) {
                    $errors[] = "No active batch available while processing {$entry['file_title']}";
                    Log::warning('AI-indexed batch assignment failed', [
                        'entry' => $entry,
                        'error' => $runtimeException->getMessage(),
                    ]);
                } catch (Exception $e) {
                    $errors[] = "Error creating entry for {$entry['file_title']}: " . $e->getMessage();
                    Log::error('Error creating AI-indexed file entry', [
                        'entry' => $entry,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $message = "Successfully processed {$createdCount} AI-indexed files";
            if (!empty($errors)) {
                $message .= " with " . count($errors) . " errors";
            }

            return response()->json([
                'success' => $createdCount > 0,
                'message' => $message,
                'created_count' => $createdCount,
                'created_files' => $createdFiles,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            Log::error('Error creating AI-indexed bulk entries', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating AI-indexed files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified file index
     */
    public function show($id)
    {
        try {
            $fileIndexing = FileIndexing::on('sqlsrv')
                ->with(['mainApplication', 'scannings', 'pagetypings'])
                ->findOrFail($id);

            $PageTitle = 'File Index Details';
            $PageDescription = 'View file index information and workflow status';

            return view('fileindexing.show', compact('PageTitle', 'PageDescription', 'fileIndexing'));
        } catch (Exception $e) {
            Log::error('Error loading file indexing details', [
                'file_indexing_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('fileindexing.index')
                ->with('error', 'File indexing record not found');
        }
    }

    /**
     * Show the form for editing the specified file index
     */
    public function edit($id)
    {
        try {
            $fileIndexing = FileIndexing::on('sqlsrv')->findOrFail($id);
            
            $PageTitle = 'Edit File Index';
            $PageDescription = 'Update file index information';

            return view('fileindexing.edit', compact('PageTitle', 'PageDescription', 'fileIndexing'));
        } catch (Exception $e) {
            Log::error('Error loading file indexing edit form', [
                'file_indexing_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('fileindexing.index')
                ->with('error', 'File indexing record not found');
        }
    }

    /**
     * Update the specified file index
     */
    public function update(Request $request, $id)
    {
        try {
            $fileIndexing = FileIndexing::on('sqlsrv')->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'file_number' => 'required|string|max:255',
                'file_title' => 'required|string|max:255',
                'land_use_type' => 'required|string|max:100',
                'plot_number' => 'nullable|string|max:100',
                'district' => 'nullable|string|max:100',
                'lga' => 'nullable|string|max:100',
                'st_fillno' => 'nullable|string|max:100',
                'serial_no' => 'nullable|string|max:100',
                'registry' => 'nullable|string|max:255',
                'batch_no' => 'nullable|string|max:100',
                'shelf_location' => 'nullable|string|max:100',
                'shelf_label_id' => 'nullable|integer',
                'tracking_id' => 'nullable|string|max:100',
                'tp_no' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'property_description' => 'nullable|string',
                'has_cofo' => 'boolean',
                'is_merged' => 'boolean',
                'has_transaction' => 'boolean',
                'is_problematic' => 'boolean',
                'is_co_owned_plot' => 'boolean',
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                } else {
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }
            }

            // Update the file indexing record
            $fileIndexing->update($validator->validated());

            // Reset batch tracking fields when file is updated
            $fileIndexing->update([
                'batch_generated' => 0,
                'last_batch_id' => null,
                'batch_generated_at' => null,
                'batch_generated_by' => null,
            ]);

            Log::info('File indexing updated', [
                'file_indexing_id' => $id,
                'updated_by' => Auth::id(),
                'batch_reset' => true
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'File indexing updated successfully!',
                    'redirect' => route('fileindexing.edit', $id)
                ]);
            } else {
                return redirect()->route('fileindexing.edit', $id)
                    ->with('success', 'File indexing updated successfully!');
            }

        } catch (Exception $e) {
            Log::error('Error updating file indexing', [
                'file_indexing_id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating file indexing: ' . $e->getMessage()
                ], 500);
            } else {
                return redirect()->back()
                    ->with('error', 'Error updating file indexing: ' . $e->getMessage())
                    ->withInput();
            }
        }
    }

    /**
     * Remove the specified file index
     */
    public function destroy($id)
    {
        try {
            $fileIndexing = FileIndexing::on('sqlsrv')->findOrFail($id);
            
            // Check if there are related scannings or page typings
            if ($fileIndexing->scannings()->exists() || $fileIndexing->pagetypings()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete file indexing with associated documents or page typings'
                ], 409);
            }

            $fileIndexing->delete();

            Log::info('File indexing deleted', [
                'file_indexing_id' => $id,
                'deleted_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File indexing deleted successfully!'
            ]);

        } catch (Exception $e) {
            Log::error('Error deleting file indexing', [
                'file_indexing_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting file indexing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search applications for file number selection (AJAX)
     * Searches both mother_applications and subapplications tables
     */
    public function searchApplications(Request $request)
    {
        try {
            $search = $request->get('search', '');
            
            // Search mother_applications table
            $motherApplications = DB::connection('sqlsrv')
                ->table('mother_applications')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('file_indexings')
                        ->whereRaw('file_indexings.main_application_id = mother_applications.id');
                })
                ->where(function ($query) use ($search) {
                    if ($search) {
                        $query->where('fileno', 'like', "%{$search}%")
                            ->orWhere('np_fileno', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('corporate_name', 'like', "%{$search}%")
                            ->orWhere('multiple_owners_names', 'like', "%{$search}%");
                    }
                })
                ->select(
                    'id',
                    'fileno',
                    'np_fileno', 
                    'first_name',
                    'middle_name',
                    'surname',
                    'applicant_title',
                    'corporate_name',
                    'rc_number',
                    'multiple_owners_names',
                    'applicant_type',
                    'land_use',
                    'property_plot_no',
                    'property_district',
                    'property_lga',
                    'property_state',
                    'created_at',
                    DB::raw("'mother' as source_table")
                )
                ->orderBy('created_at', 'desc')
                ->limit(25)
                ->get();

            // Search subapplications table with mother application data for land use
            $subApplications = DB::connection('sqlsrv')
                ->table('subapplications')
                ->leftJoin('mother_applications', 'subapplications.main_application_id', '=', 'mother_applications.id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('file_indexings')
                        ->whereRaw('file_indexings.subapplication_id = subapplications.id');
                })
                ->where(function ($query) use ($search) {
                    if ($search) {
                        $query->where('subapplications.fileno', 'like', "%{$search}%")
                            ->orWhere('subapplications.first_name', 'like', "%{$search}%")
                            ->orWhere('subapplications.surname', 'like', "%{$search}%")
                            ->orWhere('subapplications.corporate_name', 'like', "%{$search}%")
                            ->orWhere('subapplications.multiple_owners_names', 'like', "%{$search}%");
                    }
                })
                ->select(
                    'subapplications.id',
                    'subapplications.fileno',
                    DB::raw('NULL as np_fileno'),
                    'subapplications.first_name',
                    'subapplications.middle_name', 
                    'subapplications.surname',
                    'subapplications.applicant_title',
                    'subapplications.corporate_name',
                    'subapplications.rc_number',
                    'subapplications.multiple_owners_names',
                    'subapplications.applicant_type',
                    'mother_applications.land_use', // Get land use from mother application
                    'subapplications.unit_number',
                    'subapplications.block_number',
                    'subapplications.floor_number',
                    'mother_applications.property_district',
                    'mother_applications.property_lga',
                    'mother_applications.property_state',
                    'subapplications.created_at',
                    DB::raw("'sub' as source_table")
                )
                ->orderBy('subapplications.created_at', 'desc')
                ->limit(25)
                ->get();

            // Combine and format results
            $allApplications = collect($motherApplications)->merge($subApplications);

            return response()->json([
                'success' => true,
                'applications' => $allApplications->map(function ($app) {
                    return [
                        'id' => $app->id,
                        'source_table' => $app->source_table,
                        'file_number' => $app->fileno ?? $app->np_fileno ?? "APP-{$app->id}",
                        'applicant_name' => $this->getApplicantNameFromRecord($app),
                        'application_type' => $app->source_table === 'mother' ? 'Primary ' : 'Unit',
                        'land_use' => $app->land_use ?? 'Residential',
                        'plot_number' => $app->property_plot_no ?? (isset($app->unit_number) && $app->unit_number ? "Unit {$app->unit_number}" : ''),
                        'district' => $app->property_district ?? '',
                        'lga' => $app->property_lga ?? '',
                        'status' => 'Pending Index',
                        'created_at' => $app->created_at ? date('M d, Y', strtotime($app->created_at)) : '',
                        // Include all original fields for debugging
                        'applicant_type' => $app->applicant_type,
                        'first_name' => $app->first_name,
                        'middle_name' => $app->middle_name,
                        'surname' => $app->surname,
                        'applicant_title' => $app->applicant_title,
                        'corporate_name' => $app->corporate_name,
                        'rc_number' => $app->rc_number,
                        'multiple_owners_names' => $app->multiple_owners_names,
                    ];
                })->sortByDesc('created_at')->values()
            ]);

        } catch (Exception $e) {
            Log::error('Error searching applications', [
                'error' => $e->getMessage(),
                'search' => $search
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error searching applications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search file numbers for Select2 dropdown (AJAX endpoint)
     * Now includes mother_applications table with np_fileno
     */
    public function searchFileNumbers(Request $request)
    {
        try {
            $search = trim($request->get('search', ''));
            $page = (int) $request->get('page', 1);
            $limit = min((int) $request->get('limit', 20), 50); // Max 50 results per page
            $offset = ($page - 1) * $limit;

            if (strlen($search) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search term must be at least 2 characters',
                    'files' => [],
                    'has_more' => false
                ]);
            }

            $allFiles = collect();
            $totalResults = 0;

            // 1. Search in fileNumber table (existing file numbers)
            $fileNumberResults = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->select([
                    'id',
                    'kangisFileNo',
                    'mlsfNo', 
                    'NewKANGISFileNo',
                    DB::raw("'fileNumber' as source_table"),
                    DB::raw('NULL as np_fileno'),
                    DB::raw('NULL as applicant_name'),
                    DB::raw('NULL as land_use')
                ])
                ->where(function ($q) use ($search) {
                    $q->where('kangisFileNo', 'like', "%{$search}%")
                      ->orWhere('mlsfNo', 'like', "%{$search}%")
                      ->orWhere('NewKANGISFileNo', 'like', "%{$search}%");
                })
                ->where(function ($q) {
                    $q->whereNotNull('kangisFileNo')
                      ->orWhereNotNull('mlsfNo')
                      ->orWhereNotNull('NewKANGISFileNo');
                })
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            // 2. Search in mother_applications table (sectional titling applications)
            $motherAppResults = DB::connection('sqlsrv')
                ->table('mother_applications')
                ->select([
                    'id',
                    DB::raw('NULL as kangisFileNo'),
                    'fileno as mlsfNo',
                    DB::raw('NULL as NewKANGISFileNo'),
                    DB::raw("'mother_applications' as source_table"),
                    'np_fileno',
                    DB::raw("CASE 
                        WHEN applicant_type = 'individual' THEN CONCAT(COALESCE(first_name, ''), ' ', COALESCE(surname, ''))
                        WHEN applicant_type = 'corporate' THEN COALESCE(corporate_name, '')
                        WHEN applicant_type = 'multiple' THEN 'Multiple Owners'
                        ELSE 'Unknown'
                    END as applicant_name"),
                    'land_use'
                ])
                ->where(function ($q) use ($search) {
                    $q->where('np_fileno', 'like', "%{$search}%")
                      ->orWhere('fileno', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('surname', 'like', "%{$search}%")
                      ->orWhere('corporate_name', 'like', "%{$search}%");
                })
                ->where(function ($q) {
                    $q->whereNotNull('np_fileno')
                      ->orWhereNotNull('fileno');
                })
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            // Combine results
            $allFiles = $fileNumberResults->merge($motherAppResults);
            
            // Apply pagination
            $paginatedFiles = $allFiles->slice($offset, $limit);
            $hasMore = $allFiles->count() > ($offset + $limit);

            $files = $paginatedFiles->map(function ($record) {
                // Determine primary file number and type
                $fileNumber = '';
                $fileType = '';
                
                if ($record->source_table === 'mother_applications') {
                    // For mother applications, prioritize np_fileno, then fileno
                    if (!empty($record->np_fileno)) {
                        $fileNumber = $record->np_fileno;
                        $fileType = 'NP FileNo (Sectional Titling)';
                    } elseif (!empty($record->mlsfNo)) {
                        $fileNumber = $record->mlsfNo;
                        $fileType = 'Primary FileNo';
                    }
                } else {
                    // For fileNumber table, use existing logic
                    if (!empty($record->mlsfNo)) {
                        $fileNumber = $record->mlsfNo;
                        $fileType = 'MLS';
                    } elseif (!empty($record->kangisFileNo)) {
                        $fileNumber = $record->kangisFileNo;
                        $fileType = 'KANGIS';
                    } elseif (!empty($record->NewKANGISFileNo)) {
                        $fileNumber = $record->NewKANGISFileNo;
                        $fileType = 'New KANGIS';
                    }
                }

                return [
                    'id' => $record->source_table . '_' . $record->id, // Prefix with source to avoid conflicts
                    'source' => $record->source_table,
                    'application_id' => $record->id,
                    'file_number' => $fileNumber,
                    'kangis_file_no' => $record->kangisFileNo ?? '',
                    'mls_file_no' => $record->mlsfNo ?? '',
                    'new_kangis_file_no' => $record->NewKANGISFileNo ?? '',
                    'np_fileno' => $record->np_fileno ?? '',
                    'file_type' => $fileType,
                    'applicant_name' => $record->applicant_name ?? '',
                    'land_use' => $record->land_use ?? ''
                ];
            })->filter(function ($file) {
                return !empty($file['file_number']); // Only include records with valid file numbers
            })->values();

            return response()->json([
                'success' => true,
                'files' => $files,
                'has_more' => $hasMore,
                'total_found' => $files->count(),
                'page' => $page
            ]);

        } catch (Exception $e) {
            Log::error('Error searching file numbers', [
                'search' => $search,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error searching file numbers: ' . $e->getMessage(),
                'files' => [],
                'has_more' => false
            ], 500);
        }
    }

    /**
     * Get pending files count
     */
    private function getPendingFilesCount()
    {
        try {
            $motherCount = ApplicationMother::on('sqlsrv')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('file_indexings')
                        ->whereRaw('file_indexings.main_application_id = mother_applications.id');
                })
                ->count();

            $subCount = DB::connection('sqlsrv')
                ->table('subapplications')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('file_indexings')
                        ->whereRaw('file_indexings.subapplication_id = subapplications.id');
                })
                ->count();

            return $motherCount + $subCount;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get indexed today count
     */
    private function getIndexedTodayCount()
    {
        try {
            return FileIndexing::on('sqlsrv')
                ->whereDate('created_at', today())
                ->count();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get applicant name from application
     */
    private function getApplicantName($application)
    {
        if ($application->applicant_type === 'individual') {
            return trim($application->first_name . ' ' . $application->middle_name . ' ' . $application->surname);
        } elseif ($application->applicant_type === 'corporate') {
            return $application->corporate_name;
        } else {
            return 'Multiple Applicants';
        }
    }

    /**
     * Get applicant name from database record (for both mother and unit applications)
     */
    private function getApplicantNameFromRecord($record)
    {
        if ($record->applicant_type === 'individual') {
            $nameParts = [];
            if (!empty($record->applicant_title)) $nameParts[] = $record->applicant_title;
            if (!empty($record->first_name)) $nameParts[] = $record->first_name;
            if (!empty($record->middle_name)) $nameParts[] = $record->middle_name;
            if (!empty($record->surname)) $nameParts[] = $record->surname;
            
            $name = implode(' ', $nameParts);
            return $name ?: 'Unknown Individual';
        } elseif ($record->applicant_type === 'corporate') {
            $corporateName = $record->corporate_name ?? 'Unknown Corporate';
            if (!empty($record->rc_number)) {
                $corporateName .= " (RC: {$record->rc_number})";
            }
            return $corporateName;
        } elseif ($record->applicant_type === 'multiple') {
            // Handle multiple owners
            if (!empty($record->multiple_owners_names)) {
                // Check if it's JSON encoded
                $decoded = json_decode($record->multiple_owners_names, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    // If it's an array, join the first few names
                    if (count($decoded) > 2) {
                        return $decoded[0] . ' & ' . $decoded[1] . ' et al.';
                    } else {
                        return implode(' & ', $decoded);
                    }
                } else {
                    // If it's a plain string, return as is
                    return $record->multiple_owners_names;
                }
            }
            return 'Multiple Owners';
        } else {
            // Handle unknown types - try all possible name fields
            if (!empty($record->multiple_owners_names)) {
                $decoded = json_decode($record->multiple_owners_names, true);
                if (is_array($decoded) && count($decoded) > 0) {
                    return count($decoded) > 1 ? $decoded[0] . ' et al.' : $decoded[0];
                } else {
                    return $record->multiple_owners_names;
                }
            } elseif (!empty($record->corporate_name)) {
                return $record->corporate_name;
            } elseif (!empty($record->first_name) || !empty($record->surname)) {
                $nameParts = [];
                if (!empty($record->applicant_title)) $nameParts[] = $record->applicant_title;
                if (!empty($record->first_name)) $nameParts[] = $record->first_name;
                if (!empty($record->middle_name)) $nameParts[] = $record->middle_name;
                if (!empty($record->surname)) $nameParts[] = $record->surname;
                return implode(' ', $nameParts) ?: 'Unknown Applicant';
            } else {
                return 'Unknown Applicant';
            }
        }
    }

    /**
     * Get file indexing list for other modules (AJAX endpoint)
     */
    public function checkFileStatus(Request $request)
    {
        try {
            $fileno = trim($request->get('fileno', ''));
            if ($fileno === '') {
                return response()->json(['success' => false, 'message' => 'Missing fileno'], 422);
            }

            // Find file_indexings by file_number
            $fileIndex = FileIndexing::on('sqlsrv')
                ->with(['scannings', 'pagetypings'])
                ->where('file_number', $fileno)
                ->first();

            if (!$fileIndex) {
                // Try to resolve fileno from mother_applications or subapplications
                $mother = DB::connection('sqlsrv')->table('mother_applications')
                    ->where('fileno', $fileno)
                    ->orWhere('np_fileno', $fileno)
                    ->first();

                $sub = null;
                if (!$mother) {
                    $sub = DB::connection('sqlsrv')->table('subapplications')
                        ->where('fileno', $fileno)
                        ->first();
                }

                if ($mother) {
                    $fileIndex = FileIndexing::on('sqlsrv')
                        ->with(['scannings', 'pagetypings'])
                        ->where('main_application_id', $mother->id)
                        ->first();
                } elseif ($sub) {
                    $fileIndex = FileIndexing::on('sqlsrv')
                        ->with(['scannings', 'pagetypings'])
                        ->where('subapplication_id', $sub->id)
                        ->first();
                }
            }

            if (!$fileIndex) {
                return response()->json([
                    'success' => true,
                    'exists' => false,
                    'message' => 'No file indexing record found for the provided file number'
                ]);
            }

            $typedCount = $fileIndex->pagetypings ? $fileIndex->pagetypings->count() : 0;
            $scannedCount = $fileIndex->scannings ? $fileIndex->scannings->count() : 0;
            $status = 'indexed';
            if ($typedCount > 0) {
                $status = 'typed';
            } elseif ($scannedCount > 0) {
                $status = 'scanned';
            }

            return response()->json([
                'success' => true,
                'exists' => true,
                'status' => $status,
                'file_indexing' => [
                    'id' => $fileIndex->id,
                    'file_number' => $fileIndex->file_number,
                    'file_title' => $fileIndex->file_title,
                    'plot_number' => $fileIndex->plot_number,
                    'district' => $fileIndex->district,
                    'lga' => $fileIndex->lga,
                    'land_use_type' => $fileIndex->land_use_type,
                    'has_cofo' => (bool) $fileIndex->has_cofo,
                    'is_merged' => (bool) $fileIndex->is_merged,
                    'has_transaction' => (bool) $fileIndex->has_transaction,
                    'is_co_owned_plot' => (bool) $fileIndex->is_co_owned_plot,
                    'scanning_count' => $scannedCount,
                    'page_typing_count' => $typedCount,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Error checking file status', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error checking file status'
            ], 500);
        }
    }

    public function getFileIndexingList(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $status = $request->get('status', 'all'); // all, indexed, scanned, typed
            
            $query = FileIndexing::on('sqlsrv')
                ->with(['mainApplication', 'scannings', 'pagetypings']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('file_number', 'like', "%{$search}%")
                        ->orWhere('file_title', 'like', "%{$search}%")
                        ->orWhere('plot_number', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($status === 'indexed') {
                $query->whereDoesntHave('scannings');
            } elseif ($status === 'scanned') {
                $query->whereHas('scannings')
                    ->whereDoesntHave('pagetypings');
            } elseif ($status === 'typed') {
                $query->whereHas('pagetypings');
            }

            $fileIndexings = $query->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'file_indexings' => $fileIndexings->map(function ($fi) {
                    $latestBatch = $fi->getLatestBatch();
                    return [
                        'id' => $fi->id,
                        'file_number' => $fi->file_number,
                        'file_title' => $fi->file_title,
                        'plot_number' => $fi->plot_number,
                        'district' => $fi->district ?? 'No District',
                        'lga' => $fi->lga ?? 'Municipal',
                        'status' => $fi->status,
                        'scanning_count' => $fi->scannings->count(),
                        'page_typing_count' => $fi->pagetypings->count(),
                        'created_at' => $fi->created_at->format('M d, Y H:i'),
                        'batch_generated' => $fi->hasBeenInBatch(),
                        'last_batch_id' => $latestBatch ? $latestBatch->batch_id : null,
                        'batch_generated_at' => $latestBatch ? $latestBatch->generated_at->format('M d, Y H:i') : null,
                    ];
                })
            ]);

        } catch (Exception $e) {
            Log::error('Error getting file indexing list', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading file indexing list'
            ], 500);
        }
    }

    /**
     * Get pending files (applications without file indexing) - API endpoint
     */
    public function getPendingFiles(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $offset = ($page - 1) * $perPage;
            
            // Get mother applications without file indexing
            $motherApplications = DB::connection('sqlsrv')
                ->table('mother_applications')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('file_indexings')
                        ->whereRaw('file_indexings.main_application_id = mother_applications.id');
                })
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('file_indexings')
                        ->whereRaw('file_indexings.file_number = mother_applications.fileno OR file_indexings.file_number = mother_applications.np_fileno');
                })
                ->where(function ($query) use ($search) {
                    if ($search) {
                        $query->where('fileno', 'like', "%{$search}%")
                            ->orWhere('np_fileno', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('corporate_name', 'like', "%{$search}%");
                    }
                })
                ->select(
                    'id',
                    'fileno',
                    'np_fileno',
                    'first_name',
                    'middle_name',
                    'surname',
                    'applicant_title',
                    'corporate_name',
                    'applicant_type',
                    'land_use',
                    'property_plot_no',
                    'property_district',
                    'property_lga',
                    'created_at',
                    DB::raw("'mother' as source_table")
                )
                ->orderBy('created_at', 'desc')
                ->get();

            // Get sub applications without file indexing
            $subApplications = DB::connection('sqlsrv')
                ->table('subapplications')
                ->leftJoin('mother_applications', 'subapplications.main_application_id', '=', 'mother_applications.id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('file_indexings')
                        ->whereRaw('file_indexings.subapplication_id = subapplications.id');
                })
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('file_indexings')
                        ->whereRaw('file_indexings.file_number = subapplications.fileno');
                })
                ->where(function ($query) use ($search) {
                    if ($search) {
                        $query->where('subapplications.fileno', 'like', "%{$search}%")
                            ->orWhere('subapplications.first_name', 'like', "%{$search}%")
                            ->orWhere('subapplications.surname', 'like', "%{$search}%")
                            ->orWhere('subapplications.corporate_name', 'like', "%{$search}%");
                    }
                })
                ->select(
                    'subapplications.id',
                    'subapplications.fileno',
                    DB::raw('NULL as np_fileno'),
                    'subapplications.first_name',
                    'subapplications.middle_name',
                    'subapplications.surname',
                    'subapplications.applicant_title',
                    'subapplications.corporate_name',
                    'subapplications.applicant_type',
                    'mother_applications.land_use',
                    'subapplications.unit_number',
                    'mother_applications.property_district',
                    'mother_applications.property_lga',
                    'subapplications.created_at',
                    DB::raw("'sub' as source_table")
                )
                ->orderBy('subapplications.created_at', 'desc')
                ->get();

            // Combine and format results
            $allApplications = collect($motherApplications)->merge($subApplications);

            $pendingFiles = $allApplications->map(function ($app) {
                return [
                    'id' => $app->source_table . '-' . $app->id, // Prefix with table to avoid conflicts
                    'application_id' => $app->id,
                    'source_table' => $app->source_table,
                    'fileNumber' => $app->fileno ?? $app->np_fileno ?? "APP-{$app->id}",
                    'name' => $this->getApplicantNameFromRecord($app),
                    'type' => $app->source_table === 'mother' ? 'Primary Application' : 'Unit Application',
                    'source' => 'Application',
                    'date' => $app->created_at ? date('Y-m-d', strtotime($app->created_at)) : date('Y-m-d'),
                    'landUseType' => $app->land_use ?? 'Residential',
                    'district' => $app->property_district ?? 'Unknown',
                    'lga' => $app->property_lga ?? 'Kano Municipal',
                    'hasCofo' => false,
                ];
            })->sortByDesc('date')->values();

            // Apply pagination
            $total = $pendingFiles->count();
            $paginatedFiles = $pendingFiles->slice($offset, $perPage)->values();

            return response()->json([
                'success' => true,
                'pending_files' => $paginatedFiles,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total)
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error getting pending files', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading pending files'
            ], 500);
        }
    }

    /**
     * DataTables endpoint for indexed files - optimized for fast pagination/search
     */
    public function getIndexedFilesDataTable(Request $request)
    {
        try {
            $draw = (int) $request->input('draw', 0);
            $start = max(0, (int) $request->input('start', 0));
            $length = (int) $request->input('length', 25);
            $length = max(1, min($length, 100));
            $searchValue = trim((string) $request->input('search.value', ''));
            $orderColumnIndex = (int) $request->input('order.0.column', 9);
            $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
            $onlyNotGenerated = filter_var($request->input('only_not_generated'), FILTER_VALIDATE_BOOLEAN);

            $columnMap = [
                0  => 'file_indexings.tracking_id',
                1  => 'file_indexings.shelf_location',
                2  => 'file_indexings.registry',
                3  => 'file_indexings.sys_batch_no',
                4  => 'g.mdc_batch_no',                    // Changed to grouping table
                5  => 'file_indexings.group',              // Changed from group_no to group
                6  => 'file_indexings.file_number',
                7  => 'file_indexings.file_title',
                8  => 'file_indexings.plot_number',
                9  => 'file_indexings.created_at',
                10 => 'creators.last_name',
                11 => 'file_indexings.tp_no',
                12 => 'file_indexings.lpkn_no',
                13 => 'file_indexings.land_use_type',
                14 => 'file_indexings.district',
                15 => 'file_indexings.lga',
                16 => 'file_indexings.batch_generated',
            ];

            if (!array_key_exists($orderColumnIndex, $columnMap)) {
                $orderColumnIndex = 9;
            }

            $sortColumn = $columnMap[$orderColumnIndex];

            $totalRecordsQuery = FileIndexing::on('sqlsrv')
                ->where(function ($query) {
                    $query->where('is_deleted', 0)
                          ->orWhereNull('is_deleted');
                });

            if ($onlyNotGenerated) {
                $totalRecordsQuery->where(function ($query) {
                    $query->where('batch_generated', 0)
                          ->orWhereNull('batch_generated');
                });
            }

            $totalRecords = (clone $totalRecordsQuery)->count();

            $baseQuery = FileIndexing::on('sqlsrv')
                ->from('file_indexings')
                ->leftJoin('grouping as g', 'g.awaiting_fileno', '=', 'file_indexings.file_number')
                ->leftJoin('users as creators', 'creators.id', '=', 'file_indexings.created_by')
                ->where(function ($query) {
                    $query->where('file_indexings.is_deleted', 0)
                          ->orWhereNull('file_indexings.is_deleted');
                });

            if ($onlyNotGenerated) {
                $baseQuery->where(function ($query) {
                    $query->where('file_indexings.batch_generated', 0)
                          ->orWhereNull('file_indexings.batch_generated');
                });
            }

            if ($searchValue !== '') {
                $escaped = $this->escapeLikePattern($searchValue);
                $like = '%' . $escaped . '%';

                $baseQuery->where(function ($query) use ($like) {
                    $query->where('file_indexings.file_number', 'like', $like)
                        ->orWhere('file_indexings.file_title', 'like', $like)
                        ->orWhere('file_indexings.plot_number', 'like', $like)
                        ->orWhere('file_indexings.tp_no', 'like', $like)
                        ->orWhere('file_indexings.lpkn_no', 'like', $like)
                        ->orWhere('file_indexings.registry', 'like', $like)
                        ->orWhere('file_indexings.shelf_location', 'like', $like)
                        ->orWhere('file_indexings.tracking_id', 'like', $like)
                        ->orWhereRaw('[file_indexings].[group] LIKE ?', [$like])
                        ->orWhere('file_indexings.sys_batch_no', 'like', $like)
                        ->orWhere('file_indexings.land_use_type', 'like', $like)
                        ->orWhere('file_indexings.district', 'like', $like)
                        ->orWhere('file_indexings.lga', 'like', $like)
                        ->orWhere('g.batch_no', 'like', $like)
                        ->orWhere('g.sys_batch_no', 'like', $like)
                        ->orWhere('g.mdc_batch_no', 'like', $like)
                        ->orWhereRaw('g.[group] LIKE ?', [$like])
                        ->orWhere('g.registry', 'like', $like)
                        ->orWhere('g.landuse', 'like', $like)
                        ->orWhere('creators.first_name', 'like', $like)
                        ->orWhere('creators.last_name', 'like', $like)
                        ->orWhereRaw("LTRIM(RTRIM(COALESCE(creators.first_name, '') + ' ' + COALESCE(creators.last_name, ''))) LIKE ?", [$like]);
                });
            }

            $recordsFiltered = (clone $baseQuery)->distinct()->count('file_indexings.id');

            $dataQuery = clone $baseQuery;

            $dataQuery->select([
                'file_indexings.id',
                'file_indexings.file_number',
                'file_indexings.file_title',
                'file_indexings.plot_number',
                'file_indexings.tp_no',
                'file_indexings.lpkn_no',
                'file_indexings.location',
                'file_indexings.district',
                'file_indexings.registry',
                'file_indexings.lga',
                'file_indexings.land_use_type',
                'file_indexings.batch_no',
                'file_indexings.sys_batch_no',
                // 'file_indexings.mdc_batch_no', // This column doesn't exist - removed
                'file_indexings.group as group_no',  // Use 'group' column instead of 'group_no'
                'file_indexings.tracking_id',
                'file_indexings.shelf_location',
                'file_indexings.created_at',
                'file_indexings.batch_generated',
                'file_indexings.last_batch_id',
                'file_indexings.batch_generated_at',
                'file_indexings.batch_generated_by',
                'file_indexings.has_cofo',
                'g.registry as grouping_registry',
                'g.batch_no as grouping_batch_no',
                'g.sys_batch_no as grouping_sys_batch_no',
                'g.mdc_batch_no as grouping_mdc_batch_no',
                DB::raw('g.[group] as grouping_group'),
                'g.shelf_rack as grouping_shelf_rack',
                'g.landuse as grouping_landuse',
                'g.date_index as grouping_date_index',
                'g.indexed_by as grouping_indexed_by',
                'creators.first_name as creator_first_name',
                'creators.last_name as creator_last_name',
                // 'creators.name as creator_full_name',  // This column doesn't exist - removed
            ]);

            $dataQuery->orderBy($sortColumn, $orderDirection);
            if ($sortColumn !== 'file_indexings.id') {
                $dataQuery->orderBy('file_indexings.id', 'desc');
            }

            $rows = $dataQuery
                ->skip($start)
                ->take($length)
                ->get();

            $ids = $rows->pluck('id')->filter()->all();
            $scanningCounts = [];
            $pageTypingCounts = [];

            if (!empty($ids)) {
                $scanningCounts = DB::connection('sqlsrv')
                    ->table('scannings')
                    ->select('file_indexing_id', DB::raw('COUNT(*) as total'))
                    ->whereIn('file_indexing_id', $ids)
                    ->groupBy('file_indexing_id')
                    ->pluck('total', 'file_indexing_id')
                    ->all();

                $pageTypingCounts = DB::connection('sqlsrv')
                    ->table('pagetypings')
                    ->select('file_indexing_id', DB::raw('COUNT(*) as total'))
                    ->whereIn('file_indexing_id', $ids)
                    ->groupBy('file_indexing_id')
                    ->pluck('total', 'file_indexing_id')
                    ->all();
            }

            $data = collect($rows)->map(function ($row) use ($scanningCounts, $pageTypingCounts) {
                $scannedCount = (int) ($scanningCounts[$row->id] ?? 0);
                $typedCount = (int) ($pageTypingCounts[$row->id] ?? 0);

                $source = 'Indexed';
                if ($typedCount > 0) {
                    $source = 'Indexed & Typed';
                } elseif ($scannedCount > 0) {
                    $source = 'Indexed & Scanned';
                }

                $registry = $row->registry ?? $row->grouping_registry;
                $batchNo = $row->batch_no ?? $row->grouping_batch_no;
                $sysBatch = $row->sys_batch_no ?? $row->grouping_sys_batch_no ?? $batchNo;
                $mdcBatch = $row->grouping_mdc_batch_no ?? $batchNo;  // Fixed: only use grouping table
                $groupNo = $row->group_no ?? $row->grouping_group;
                $shelfLocation = $row->shelf_location ?? $row->grouping_shelf_rack;
                $landUse = $row->land_use_type ?? $row->grouping_landuse ?? 'Residential';

                $timestamp = $row->created_at ?? $row->grouping_date_index;
                $indexedAt = null;
                $indexedDate = null;
                if ($timestamp) {
                    $carbon = $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);
                    $indexedAt = $carbon->format('Y-m-d H:i');
                    $indexedDate = $carbon->format('Y-m-d');
                }

                $indexedBy = $row->grouping_indexed_by;
                if (!$indexedBy) {
                    $nameFromParts = trim(collect([$row->creator_first_name, $row->creator_last_name])->filter()->implode(' '));
                    if ($nameFromParts !== '') {
                        $indexedBy = $nameFromParts;
                    }
                    // Removed reference to creator_full_name since it does not exist
                }

                $batchGeneratedAt = null;
                if (!empty($row->batch_generated_at)) {
                    $batchGeneratedAt = $row->batch_generated_at instanceof Carbon
                        ? $row->batch_generated_at->format('Y-m-d H:i')
                        : Carbon::parse($row->batch_generated_at)->format('Y-m-d H:i');
                }

                $documentType = $this->resolveDocumentTypeFromValues(
                    $row->land_use_type ?? $row->grouping_landuse,
                    (bool) $row->has_cofo
                );

                return [
                    'id' => $row->id,
                    'tracking_id' => $row->tracking_id,
                    'shelf_location' => $shelfLocation,
                    'registry' => $registry,
                    'sys_batch_no' => $sysBatch,
                    'mdc_batch_no' => $mdcBatch,
                    'group_no' => $groupNo,
                    'fileNumber' => $row->file_number,
                    'name' => $row->file_title,
                    'plotNumber' => $row->plot_number,
                    'indexed_at' => $indexedAt,
                    'date' => $indexedDate,
                    'indexed_by' => $indexedBy,
                    'tpNumber' => $row->tp_no,
                    'lpknNumber' => $row->lpkn_no,
                    'landUseType' => $landUse,
                    'district' => $row->district ?? 'Unknown',
                    'lga' => $row->lga ?? 'Kano Municipal',
                    'location' => $row->location,
                    'source' => $source,
                    'type' => $documentType,
                    'batch_generated' => (bool) $row->batch_generated,
                    'last_batch_id' => $row->last_batch_id,
                    'batch_generated_at' => $batchGeneratedAt,
                    'batch_generated_by' => $row->batch_generated_by,
                    'scanning_count' => $scannedCount,
                    'page_typing_count' => $typedCount,
                ];
            })->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            Log::error('Error building indexed files datatable', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unable to load indexed files at this time.',
            ], 500);
        }
    }

    /**
     * Get indexed files - legacy API endpoint used by other modules
     */
    public function getIndexedFiles(Request $request)
    {
        try {
            $search = trim((string) $request->get('search', ''));
            $page = max(1, (int) $request->get('page', 1));
            $perPage = (int) $request->get('per_page', 10);
            $perPage = max(1, min($perPage, 50)); // guard against pathological requests
            $onlyNotGenerated = $request->get('only_not_generated', false);
            $offset = ($page - 1) * $perPage;

            $query = FileIndexing::on('sqlsrv')
                ->select([
                    'file_indexings.id',
                    'file_indexings.file_number',
                    'file_indexings.file_title',
                    'file_indexings.plot_number',
                    'file_indexings.tp_no',
                    'file_indexings.lpkn_no',
                    'file_indexings.location',
                    'file_indexings.district',
                    'file_indexings.registry',
                    'file_indexings.lga',
                    'file_indexings.land_use_type',
                    'file_indexings.batch_no',
                    'file_indexings.sys_batch_no',
                    DB::raw('[file_indexings].[group] as group_no'),
                    'file_indexings.tracking_id',
                    'file_indexings.shelf_location',
                    'file_indexings.created_at',
                    'file_indexings.batch_generated',
                    'file_indexings.last_batch_id',
                    'file_indexings.batch_generated_at',
                    'file_indexings.batch_generated_by',
                    'file_indexings.has_cofo',
                    'file_indexings.is_merged',
                    'file_indexings.has_transaction',
                    'file_indexings.is_problematic',
                    'file_indexings.is_co_owned_plot',
                    'file_indexings.workflow_status',
                    'file_indexings.updated_at',
                ])
                ->withCount(['scannings', 'pagetypings'])
                ->with([
                    'creator:id,first_name,last_name',
                    'grouping:id,awaiting_fileno,registry,batch_no,sys_batch_no,mdc_batch_no,group,shelf_rack,landuse,date_index,indexed_by',
                ])
                ->where(function ($q) {
                    $q->where('is_deleted', 0)
                        ->orWhereNull('is_deleted');
                });

            // Filter for only not-generated files if requested
            if ($onlyNotGenerated) {
                $query->where(function ($q) {
                    $q->where('batch_generated', 0)
                      ->orWhereNull('batch_generated');
                });
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('file_number', 'like', "%{$search}%")
                        ->orWhere('file_title', 'like', "%{$search}%")
                        ->orWhere('plot_number', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%");
                });
            }

            // Get total count before pagination
            $total = $query->count();

            // Order by batch_generated first (non-generated files first), then by created_at desc
            $fileIndexings = $query->orderBy('batch_generated', 'asc')
                ->orderBy('created_at', 'desc')
                ->forPage($page, $perPage)
                ->get();

            $indexedFiles = $fileIndexings->map(function ($fi) {
                $scannedCount = (int) ($fi->scannings_count ?? 0);
                $typedCount = (int) ($fi->pagetypings_count ?? 0);
                $grouping = $fi->grouping;
                
                $source = 'Indexed';
                if ($typedCount > 0) {
                    $source = 'Indexed & Typed';
                } elseif ($scannedCount > 0) {
                    $source = 'Indexed & Scanned';
                }

                $registry = $fi->registry ?? optional($grouping)->registry;
                $batchNo = $fi->batch_no ?? optional($grouping)->batch_no;
                $sysBatchNo = $fi->sys_batch_no ?? optional($grouping)->sys_batch_no;
                $mdcBatchNo = optional($grouping)->mdc_batch_no ?? ($fi->mdc_batch_no ?? $batchNo);
                $groupNo = $fi->group_no ?? optional($grouping)->group;
                $shelfLocation = $fi->shelf_location ?? optional($grouping)->shelf_rack;

                $indexedTimestamp = $fi->created_at ?? optional($grouping)->date_index;

                if ($indexedTimestamp && !($indexedTimestamp instanceof Carbon)) {
                    $indexedTimestamp = Carbon::parse($indexedTimestamp);
                }
                $indexedAt = $indexedTimestamp ? $indexedTimestamp->format('Y-m-d H:i') : null;
                $indexedDate = $indexedTimestamp ? $indexedTimestamp->format('Y-m-d') : null;

                $indexedBy = $fi->indexed_by ?? optional($grouping)->indexed_by;

                if ($indexedBy === null && $fi->creator) {
                    $nameFromParts = trim(collect([
                        $fi->creator->first_name ?? null,
                        $fi->creator->last_name ?? null,
                    ])->filter()->join(' '));

                    $indexedBy = $nameFromParts !== ''
                        ? $nameFromParts
                        : ($fi->creator->name ?? null);
                }

                return [
                    'id' => $fi->id,
                    'fileNumber' => $fi->file_number,
                    'name' => $fi->file_title,
                    'registry' => $registry,
                    'batch_no' => $batchNo,
                    'sys_batch_no' => $sysBatchNo,
                    'mdc_batch_no' => $mdcBatchNo,
                    'group_no' => $groupNo,
                    'tracking_id' => $fi->tracking_id ?? null,
                    'shelf_location' => $shelfLocation,
                    'type' => $this->getDocumentType($fi),
                    'source' => $source,
                    'indexed_at' => $indexedAt,
                    'date' => $indexedDate,
                    'landUseType' => $fi->land_use_type ?? optional($grouping)->landuse ?? 'Residential',
                    'district' => $fi->district ?? 'Unknown',
                    'lga' => $fi->lga ?? 'Kano Municipal',
                    'hasCofo' => (bool) $fi->has_cofo,
                    'plot_number' => $fi->plot_number,
                    'plotNumber' => $fi->plot_number,
                    'tpNumber' => $fi->tp_no,
                    'lpknNumber' => $fi->lpkn_no,
                    'location' => $fi->location,
                    'indexed_by' => $indexedBy,
                    'scanning_count' => $scannedCount,
                    'page_typing_count' => $typedCount,
                    'is_merged' => (bool) $fi->is_merged,
                    'has_transaction' => (bool) $fi->has_transaction,
                    'is_problematic' => (bool) $fi->is_problematic,
                    'is_co_owned_plot' => (bool) $fi->is_co_owned_plot,
                    // New batch tracking fields
                    'batch_generated' => (bool) $fi->batch_generated,
                    'last_batch_id' => $fi->last_batch_id,
                    'batch_generated_at' => $this->formatBatchGeneratedAt($fi->batch_generated_at),
                    'batch_generated_by' => $fi->batch_generated_by,
                ];
            });

            return response()->json([
                'success' => true,
                'indexed_files' => $indexedFiles,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total)
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error getting indexed files', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading indexed files'
            ], 500);
        }
    }

    /**
     * Get document type based on file indexing data
     */
    private function getDocumentType($fileIndexing)
    {
        return $this->resolveDocumentTypeFromValues($fileIndexing->land_use_type, (bool) $fileIndexing->has_cofo);
    }

    /**
     * Generate tracking sheet for a single file
     */
    public function generateTrackingSheet($id)
    {
        try {
            $fileIndexing = FileIndexing::on('sqlsrv')
                ->with(['mainApplication', 'scannings', 'pagetypings'])
                ->findOrFail($id);

            // Get or create tracking record
            $tracker = $this->getOrCreateTracker($fileIndexing);

            // Update batch tracking fields when tracking sheet is generated
            $batchId = date('Ymd') . '_' . $id; // Create unique batch ID with date and file ID
            $fileIndexing->update([
                'batch_generated' => 1,
                'last_batch_id' => $batchId,
                'batch_generated_at' => now(),
                'batch_generated_by' => Auth::id(),
            ]);

            Log::info('Tracking sheet generated', [
                'file_indexing_id' => $id,
                'batch_id' => $batchId,
                'generated_by' => Auth::id()
            ]);

            $PageTitle = 'File Tracking Sheet';
            $PageDescription = 'Generate tracking sheet for file indexing record';
            $settings = settings(); // Add missing settings variable

            return view('fileindexing.tracking-sheet', compact('PageTitle', 'PageDescription', 'fileIndexing', 'tracker', 'settings'));
        } catch (Exception $e) {
            Log::error('Error generating tracking sheet', [
                'file_indexing_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('fileindexing.index')
                ->with('error', 'Error generating tracking sheet: ' . $e->getMessage());
        }
    }

    /**
     * Print tracking sheet for a single file
     */
    public function printTrackingSheet($id)
    {
        try {
            $fileIndexing = FileIndexing::on('sqlsrv')
                ->with(['mainApplication', 'scannings', 'pagetypings'])
                ->findOrFail($id);

            // Get or create tracking record
            $tracker = $this->getOrCreateTracker($fileIndexing);
            
            // Update print count and timestamp
            $tracker->incrementPrintCount();

            // Update batch tracking fields when tracking sheet is printed
            $batchId = date('Ymd') . '_' . $id; // Create unique batch ID with date and file ID
            $fileIndexing->update([
                'batch_generated' => 1,
                'last_batch_id' => $batchId,
                'batch_generated_at' => now(),
                'batch_generated_by' => Auth::id(),
            ]);

            Log::info('Tracking sheet printed', [
                'file_indexing_id' => $id,
                'batch_id' => $batchId,
                'printed_by' => Auth::id()
            ]);

            $PageTitle = 'Print Tracking Sheet';
            $PageDescription = 'Print tracking sheet for file indexing record';

            return view('fileindexing.print-tracking-sheet', compact('PageTitle', 'PageDescription', 'fileIndexing', 'tracker'));
        } catch (Exception $e) {
            Log::error('Error printing tracking sheet', [
                'file_indexing_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('fileindexing.index')
                ->with('error', 'Error printing tracking sheet: ' . $e->getMessage());
        }
    }

    /**
     * Generate batch tracking sheets for multiple files
     */
    public function generateBatchTrackingSheet(Request $request)
    {
        try {
            $fileIds = $request->get('files', '');
            
            if (empty($fileIds)) {
                return redirect()->route('fileindexing.index')
                    ->with('error', 'No files selected for batch tracking sheet generation');
            }

            $fileIdsArray = explode(',', $fileIds);
            
            $fileIndexings = FileIndexing::on('sqlsrv')
                ->with(['mainApplication', 'scannings', 'pagetypings'])
                ->whereIn('id', $fileIdsArray)
                ->get();

            if ($fileIndexings->isEmpty()) {
                return redirect()->route('fileindexing.index')
                    ->with('error', 'No valid files found for tracking sheet generation');
            }

            // Determine batch type based on file count
            $fileCount = $fileIndexings->count();
            $batchType = match (true) {
                $fileCount == 100 => 'auto_100',
                $fileCount == 200 => 'auto_200',
                default => 'manual'
            };

            // Save batch tracking history
            $trackingSheet = $this->saveBatchTrackingHistory($fileIdsArray, $batchType);

            // Update batch tracking fields for all files in the batch
            $this->updateBatchTrackingFields($fileIndexings, $trackingSheet);

            // Create or get trackers for all files
            $trackersData = [];
            foreach ($fileIndexings as $fileIndexing) {
                $tracker = $this->getOrCreateTracker($fileIndexing);
                $tracker->incrementPrintCount(); // Count batch print
                $trackersData[$fileIndexing->id] = $tracker;
            }

            $PageTitle = 'Batch Tracking Sheets';
            $PageDescription = 'Generate tracking sheets for multiple file indexing records';

            // Pass tracking sheet info to the view for reference
            $batchInfo = $trackingSheet ? [
                'batch_id' => $trackingSheet->batch_id,
                'batch_name' => $trackingSheet->batch_name,
                'generated_at' => $trackingSheet->generated_at->format('M d, Y g:i A')
            ] : null;

            return view('fileindexing.batch-tracking-sheet', compact(
                'PageTitle', 
                'PageDescription', 
                'fileIndexings', 
                'trackersData',
                'batchInfo'
            ));
        } catch (Exception $e) {
            Log::error('Error generating batch tracking sheets', [
                'file_ids' => $request->get('files', ''),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('fileindexing.index')
                ->with('error', 'Error generating batch tracking sheets: ' . $e->getMessage());
        }
    }

    /**
     * Get or create tracking record for file indexing
     */
    private function getOrCreateTracker($fileIndexing)
    {
        $tracker = IndexedFileTracker::on('sqlsrv')
            ->where('file_indexing_id', $fileIndexing->id)
            ->first();

        if (!$tracker) {
            // Create new tracking record
            $tracker = IndexedFileTracker::on('sqlsrv')->create([
                'file_indexing_id' => $fileIndexing->id,
                'tracking_id' => $this->generateUniqueTrackingId($fileIndexing->id),
                'current_location' => 'File Indexing Department',
                'current_handler' => Auth::user()->name ?? 'System User',
                'current_department' => 'File Indexing Department',
                'status' => 'Active',
                'priority' => 'Normal',
                'sheet_generated_at' => now(),
                'movement_history' => $this->createInitialMovementHistory($fileIndexing),
            ]);

            Log::info('Created new tracking record', [
                'file_indexing_id' => $fileIndexing->id,
                'tracking_id' => $tracker->tracking_id,
                'created_by' => Auth::id()
            ]);
        }

        return $tracker;
    }

    /**
     * Generate unique tracking ID with format TRK-XXXXXXXX-XXXXX
     */
    private function generateUniqueTrackingId($fileIndexingId)
    {
        // Generate random alphanumeric segments
        $segment1 = $this->generateRandomAlphanumeric(8); // 8 characters like MESALDX6
        $segment2 = $this->generateRandomAlphanumeric(5); // 5 characters like QWB08
        
        $baseId = "TRK-{$segment1}-{$segment2}";
        
        // Check if ID already exists and regenerate if needed
        $counter = 0;
        $trackingId = $baseId;
        
        while (IndexedFileTracker::on('sqlsrv')->where('tracking_id', $trackingId)->exists()) {
            $counter++;
            // If collision occurs, generate new segments
            $segment1 = $this->generateRandomAlphanumeric(8);
            $segment2 = $this->generateRandomAlphanumeric(5);
            $trackingId = "TRK-{$segment1}-{$segment2}";
            
            // Prevent infinite loop
            if ($counter > 100) {
                break;
            }
        }
        
        return $trackingId;
    }

    /**
     * Generate random alphanumeric string
     */
    private function generateRandomAlphanumeric($length)
    {
        $characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789'; // Exclude O, 0 for clarity
        $result = '';
        $charactersLength = strlen($characters);
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $result;
    }

    /**
     * Create initial movement history for new tracking record
     */
    private function createInitialMovementHistory($fileIndexing)
    {
        $history = [];
        
        // Add file indexing entry
        $history[] = [
            'date' => $fileIndexing->created_at->format('Y-m-d'),
            'time' => $fileIndexing->created_at->format('g:i A'),
            'location' => 'File Indexing System',
            'handler' => 'System User',
            'action' => 'File indexed and registered',
            'method' => 'Digital',
            'notes' => 'File information captured in EDMS',
            'timestamp' => $fileIndexing->created_at->toISOString(),
        ];

        // Add scanning entries if exist
        if ($fileIndexing->scannings && $fileIndexing->scannings->count() > 0) {
            $latestScanning = $fileIndexing->scannings->sortBy('created_at')->last();
            $history[] = [
                'date' => $latestScanning->created_at->format('Y-m-d'),
                'time' => $latestScanning->created_at->format('g:i A'),
                'location' => 'Scanning Department',
                'handler' => 'Scanner Operator',
                'action' => 'Document scanning completed',
                'method' => 'Digital Scan',
                'notes' => $fileIndexing->scannings->count() . ' documents scanned',
                'timestamp' => $latestScanning->created_at->toISOString(),
            ];
        }

        // Add page typing entries if exist
        if ($fileIndexing->pagetypings && $fileIndexing->pagetypings->count() > 0) {
            $latestPageTyping = $fileIndexing->pagetypings->sortBy('created_at')->last();
            $history[] = [
                'date' => $latestPageTyping->created_at->format('Y-m-d'),
                'time' => $latestPageTyping->created_at->format('g:i A'),
                'location' => 'Page Typing Department',
                'handler' => 'Data Entry Operator',
                'action' => 'Page typing completed',
                'method' => 'Manual Input',
                'notes' => $fileIndexing->pagetypings->count() . ' pages typed',
                'timestamp' => $latestPageTyping->created_at->toISOString(),
            ];
        }

        // Sort by timestamp (newest first)
        usort($history, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return $history;
    }

    /**
     * Update file tracking location (AJAX endpoint)
     */
    public function updateTrackingLocation(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'location' => 'required|string|max:255',
                'handler' => 'required|string|max:255',
                'action' => 'required|string|max:255',
                'method' => 'nullable|string|max:50',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fileIndexing = FileIndexing::on('sqlsrv')->findOrFail($id);
            $tracker = $this->getOrCreateTracker($fileIndexing);

            // Add movement record
            $tracker->addMovementRecord(
                $request->location,
                $request->handler,
                $request->action,
                $request->method ?? 'Manual',
                $request->notes ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Tracking location updated successfully',
                'tracker' => [
                    'current_location' => $tracker->current_location,
                    'current_handler' => $tracker->current_handler,
                    'last_location_update' => $tracker->last_location_update->format('M d, Y g:i A'),
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error updating tracking location', [
                'file_indexing_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating tracking location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show smart batch tracking interface
     */
    public function batchTrackingInterface(Request $request)
    {
        try {
            $fileIds = $request->get('files', '');
            $batchId = $request->get('batch_id', '');
            
            // If batch_id is provided, get the file IDs from the TrackingSheet model
            if (!empty($batchId) && empty($fileIds)) {
                $trackingSheet = \App\Models\TrackingSheet::where('batch_id', $batchId)->first();
                
                if ($trackingSheet && !empty($trackingSheet->selected_file_ids)) {
                    // Get file IDs from the JSON stored in selected_file_ids
                    $fileIdsArray = json_decode($trackingSheet->selected_file_ids, true);
                    
                    if (empty($fileIdsArray)) {
                        return redirect()->route('fileindexing.index')
                            ->with('error', 'No file IDs found in batch: ' . $batchId);
                    }
                    
                    $fileIds = implode(',', $fileIdsArray);
                    
                    // Update print count for reprint
                    $trackingSheet->increment('print_count');
                    $trackingSheet->update([
                        'last_printed_at' => now(),
                        'last_printed_by' => auth()->id()
                    ]);
                    
                    Log::info('Batch reprinted', [
                        'batch_id' => $batchId,
                        'file_ids' => $fileIdsArray,
                        'print_count' => $trackingSheet->print_count
                    ]);
                } else {
                    return redirect()->route('fileindexing.index')
                        ->with('error', 'No files found for the specified batch ID: ' . $batchId);
                }
            }
            
            if (empty($fileIds)) {
                return redirect()->route('fileindexing.index')
                    ->with('error', 'No files selected for batch tracking operations');
            }

            $fileIdsArray = explode(',', $fileIds);
            
            $selectedFiles = FileIndexing::on('sqlsrv')
                ->with(['mainApplication', 'scannings', 'pagetypings'])
                ->whereIn('id', $fileIdsArray)
                ->get()
                ->map(function ($fi) {
                    return [
                        'id' => $fi->id,
                        'file_number' => $fi->file_number,
                        'file_title' => $fi->file_title,
                        'plot_number' => $fi->plot_number,
                        'district' => $fi->district,
                        'land_use_type' => $fi->land_use_type,
                        'created_at' => $fi->created_at,
                        'updated_at' => $fi->updated_at,
                        'scanning_count' => $fi->scannings->count(),
                        'page_typing_count' => $fi->pagetypings->count(),
                    ];
                });

            if ($selectedFiles->isEmpty()) {
                return redirect()->route('fileindexing.index')
                    ->with('error', 'No valid files found for batch tracking operations');
            }

            $PageTitle = 'Smart Batch Tracking Interface';
            $PageDescription = 'Manage batch tracking operations and movement history';

            return view('fileindexing.batch-tracking-interface', compact(
                'PageTitle', 
                'PageDescription', 
                'selectedFiles'
            ));

        } catch (Exception $e) {
            Log::error('Error loading batch tracking interface', [
                'file_ids' => $request->get('files', ''),
                'batch_id' => $request->get('batch_id', ''),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('fileindexing.index')
                ->with('error', 'Error loading batch tracking interface: ' . $e->getMessage());
        }
    }

    /**
     * Process bulk movement update (AJAX endpoint)
     */
    public function bulkMovementUpdate(Request $request)
    {
        try {
            // ...existing code...
            
            $files = $request->input('files', []);
            $location = $request->input('location');
            $handler = $request->input('handler');
            $status = $request->input('status');
            $priority = $request->input('priority');
            $reason = $request->input('reason');
            $notes = $request->input('notes');

            if (empty($files) || !$location || !$handler || !$status || !$priority) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required fields: location, handler, status, and priority are required'
                ]);
            }

            $updated = 0;
            $errors = [];

            foreach ($files as $fileId) {
                try {
                    $file = FileIndex::find($fileId);
                    if (!$file) {
                        $errors[] = "File ID {$fileId} not found";
                        continue;
                    }

                    // Update file location and tracking info
                    $file->current_location = $location;
                    $file->handler = $handler;
                    $file->status = $status;
                    $file->priority = $priority;
                    $file->movement_reason = $reason;
                    $file->last_movement_date = now();
                    $file->save();

                    // Create movement log entry
                    FileMovementLog::create([
                        'file_index_id' => $file->id,
                        'file_number' => $file->file_number,
                        'previous_location' => $file->getOriginal('current_location') ?? 'Unknown',
                        'new_location' => $location,
                        'handler' => $handler,
                        'status' => $status,
                        'priority' => $priority,
                        'reason' => $reason,
                        'notes' => $notes,
                        'moved_by' => Auth::id(),
                        'moved_at' => now()
                    ]);

                    $updated++;
                } catch (Exception $e) {
                    $errors[] = "Error updating file {$fileId}: " . $e->getMessage();
                }
            }

            if ($updated > 0) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully updated {$updated} file(s)" . 
                                ($errors ? ". Errors: " . implode(', ', $errors) : '')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No files were updated. Errors: ' . implode(', ', $errors)
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get dashboard statistics - API endpoint
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'pending_files' => $this->getPendingFilesCount(),
                'indexed_today' => $this->getIndexedTodayCount(),
                'total_indexed' => FileIndexing::on('sqlsrv')->count(),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Error getting dashboard statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading statistics',
                'statistics' => [
                    'pending_files' => 0,
                    'indexed_today' => 0,
                    'total_indexed' => 0
                ]
            ]);
        }
    }

    /**
     * Get movement history for files (AJAX endpoint)
     */
    public function getMovementHistory(Request $request)
    {
        try {
            $fileIds = $request->get('files', []);
            
            if (empty($fileIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file IDs provided'
                ], 422);
            }

            if (is_string($fileIds)) {
                $fileIds = explode(',', $fileIds);
            }

            $trackers = IndexedFileTracker::on('sqlsrv')
                ->with('fileIndexing')
                ->whereIn('file_indexing_id', $fileIds)
                ->get();

            $movementHistory = [];

            foreach ($trackers as $tracker) {
                $fileIndexing = $tracker->fileIndexing;
                $history = $tracker->movement_history ?? [];

                foreach ($history as $movement) {
                    $movementHistory[] = [
                        'file_id' => $fileIndexing->id,
                        'file_number' => $fileIndexing->file_number,
                        'file_title' => $fileIndexing->file_title,
                        'tracking_id' => $tracker->tracking_id,
                        'date' => $movement['date'] ?? '',
                        'time' => $movement['time'] ?? '',
                        'location' => $movement['location'] ?? '',
                        'handler' => $movement['handler'] ?? '',
                        'action' => $movement['action'] ?? '',
                        'method' => $movement['method'] ?? '',
                        'notes' => $movement['notes'] ?? '',
                        'timestamp' => $movement['timestamp'] ?? '',
                        'current_location' => $tracker->current_location,
                        'current_handler' => $tracker->current_handler,
                        'status' => $tracker->status,
                        'priority' => $tracker->priority,
                    ];
                }
            }

            // Sort by timestamp (newest first)
            usort($movementHistory, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
            });

            return response()->json([
                'success' => true,
                'movement_history' => $movementHistory
            ]);

        } catch (Exception $e) {
            Log::error('Error getting movement history', [
                'file_ids' => $fileIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading movement history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export movement history (AJAX endpoint)
     */
    public function exportMovementHistory(Request $request)
    {
        try {
            $fileIds = $request->get('files', []);
            $format = $request->get('format', 'csv'); // csv, excel, pdf
            
            if (empty($fileIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file IDs provided'
                ], 422);
            }

            if (is_string($fileIds)) {
                $fileIds = explode(',', $fileIds);
            }

            // Get movement history data
            $historyResponse = $this->getMovementHistory($request);
            $historyData = json_decode($historyResponse->getContent(), true);

            if (!$historyData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading movement history for export'
                ], 500);
            }

            $movements = $historyData['movement_history'];

            // For now, return CSV format
            $csvData = "File Number,File Title,Tracking ID,Date,Time,Location,Handler,Action,Method,Notes,Current Location,Status,Priority\n";
            
            foreach ($movements as $movement) {
                $csvData .= implode(',', [
                    '"' . ($movement['file_number'] ?? '') . '"',
                    '"' . ($movement['file_title'] ?? '') . '"',
                    '"' . ($movement['tracking_id'] ?? '') . '"',
                    '"' . ($movement['date'] ?? '') . '"',
                    '"' . ($movement['time'] ?? '') . '"',
                    '"' . ($movement['location'] ?? '') . '"',
                    '"' . ($movement['handler'] ?? '') . '"',
                    '"' . ($movement['action'] ?? '') . '"',
                    '"' . ($movement['method'] ?? '') . '"',
                    '"' . ($movement['notes'] ?? '') . '"',
                    '"' . ($movement['current_location'] ?? '') . '"',
                    '"' . ($movement['status'] ?? '') . '"',
                    '"' . ($movement['priority'] ?? '') . '"',
                ]) . "\n";
            }

            $filename = 'movement_history_' . date('Y-m-d_H-i-s') . '.csv';

            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (Exception $e) {
            Log::error('Error exporting movement history', [
                'file_ids' => $fileIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error exporting movement history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get selected files data for AI insights (AJAX endpoint)
     */
    public function getSelectedFilesForAiInsights(Request $request)
    {
        try {
            $fileIds = $request->get('files', []);
            
            if (empty($fileIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file IDs provided'
                ], 422);
            }

            if (is_string($fileIds)) {
                $fileIds = explode(',', $fileIds);
            }

            // Get pending files data for AI insights
            $selectedFilesData = [];

            foreach ($fileIds as $fileId) {
                // Parse the fileId format (e.g., "mother-123" or "sub-456")
                if (strpos($fileId, '-') !== false) {
                    [$sourceTable, $applicationId] = explode('-', $fileId, 2);
                    
                    if ($sourceTable === 'mother') {
                        $application = DB::connection('sqlsrv')
                            ->table('mother_applications')
                            ->where('id', $applicationId)
                            ->first();
                            
                        if ($application) {
                            $selectedFilesData[] = [
                                'id' => $fileId,
                                'file_number' => $application->np_fileno ?? $application->fileno ?? "APP-{$application->id}",
                                'applicant_name' => $this->getApplicantNameFromRecord($application),
                                'document_type' => $this->getDocumentTypeFromLandUse($application->land_use ?? 'Residential'),
                                'land_use' => $application->land_use ?? 'Residential',
                                'plot_number' => $application->property_plot_no ?? 'PL-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT),
                                'district' => $application->property_district ?? 'Unknown',
                                'lga' => $application->property_lga ?? 'Kano Municipal',
                                'confidence' => rand(85, 95), // Simulated AI confidence
                                'extracted_data' => $this->generateAiExtractedData($application),
                                'ai_findings' => $this->generateAiFindings(),
                                'suggested_keywords' => $this->generateSuggestedKeywords($application),
                                'potential_issues' => $this->generatePotentialIssues($application),
                            ];
                        }
                    } elseif ($sourceTable === 'sub') {
                        $application = DB::connection('sqlsrv')
                            ->table('subapplications')
                            ->leftJoin('mother_applications', 'subapplications.main_application_id', '=', 'mother_applications.id')
                            ->where('subapplications.id', $applicationId)
                            ->select([
                                'subapplications.*',
                                'mother_applications.land_use',
                                'mother_applications.property_district',
                                'mother_applications.property_lga'
                            ])
                            ->first();
                            
                        if ($application) {
                            $selectedFilesData[] = [
                                'id' => $fileId,
                                'file_number' => $application->fileno ?? "SUB-{$application->id}",
                                'applicant_name' => $this->getApplicantNameFromRecord($application),
                                'document_type' => 'Unit Certificate',
                                'land_use' => $application->land_use ?? 'Residential',
                                'plot_number' => isset($application->unit_number) && $application->unit_number ? "Unit {$application->unit_number}" : 'Unit TBA',
                                'district' => $application->property_district ?? 'Unknown',
                                'lga' => $application->property_lga ?? 'Kano Municipal',
                                'confidence' => rand(85, 95), // Simulated AI confidence
                                'extracted_data' => $this->generateAiExtractedData($application),
                                'ai_findings' => $this->generateAiFindings(),
                                'suggested_keywords' => $this->generateSuggestedKeywords($application),
                                'potential_issues' => $this->generatePotentialIssues($application),
                            ];
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'selected_files' => $selectedFilesData
            ]);

        } catch (Exception $e) {
            Log::error('Error getting selected files for AI insights', [
                'file_ids' => $fileIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading selected files data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get document type from land use
     */
    private function getDocumentTypeFromLandUse($landUse)
    {
        $types = [
            'Residential' => 'Certificate of Occupancy',
            'Commercial' => 'Commercial Certificate',
            'Industrial' => 'Industrial Certificate',
            'Mixed Development' => 'Mixed Use Certificate',
            'Educational' => 'Educational Use Certificate',
            'Religious' => 'Religious Use Certificate',
            'Agricultural' => 'Agricultural Certificate',
        ];

        return $types[$landUse] ?? 'Certificate of Occupancy';
    }

    /**
     * Generate AI extracted data simulation
     */
    private function generateAiExtractedData($application)
    {
        return [
            'text_quality' => rand(85, 98),
            'document_structure' => rand(2, 5) > 3 ? 'Complete sections' : 'Missing sections',
            'signature_detected' => rand(1, 10) > 6 ? 'Detected' : 'Not detected',
            'stamp_detected' => rand(1, 10) > 4 ? 'Official stamp detected' : 'No stamp detected',
            'gis_verification' => rand(1, 10) > 3 ? 'Matched with parcel data' : 'No GIS match found',
        ];
    }

    /**
     * Generate AI findings simulation
     */
    private function generateAiFindings()
    {
        $findings = [
            ['label' => 'Text Quality', 'value' => rand(85, 98) . '%'],
            ['label' => 'Document Structure', 'value' => rand(2, 5) > 3 ? 'Complete sections' : 'Missing sections'],
            ['label' => 'Signature', 'value' => rand(1, 10) > 6 ? 'Detected' : 'Not detected'],
            ['label' => 'Stamp', 'value' => rand(1, 10) > 4 ? 'Official stamp detected' : 'No stamp detected'],
            ['label' => 'GIS Verification', 'value' => rand(1, 10) > 3 ? 'Matched with parcel data' : 'No GIS match found'],
        ];

        return $findings;
    }

    /**
     * Generate suggested keywords simulation
     */
    private function generateSuggestedKeywords($application)
    {
        $baseKeywords = ['Property', 'Kano State'];
        
        $landUse = $application->land_use ?? 'Residential';
        $district = $application->property_district ?? $application->district ?? '';
        
        $keywords = array_merge($baseKeywords, [$landUse]);
        
        if ($district) {
            $keywords[] = $district;
        }

        // Add document type keyword
        if ($landUse === 'Residential') {
            $keywords[] = 'Certificate of Occupancy';
            $keywords[] = 'Housing';
        } elseif ($landUse === 'Commercial') {
            $keywords[] = 'Business';
            $keywords[] = 'Commercial Certificate';
        }

        $keywords[] = 'Land Document';

        return array_unique($keywords);
    }

    /**
     * Generate potential issues simulation
     */
    private function generatePotentialIssues($application)
    {
        $possibleIssues = [
            'Plot boundaries not specified',
            'Ownership information unclear',
            'Parcel data needs updating',
            'Missing signature verification',
            'Incomplete document sections',
            'GIS coordinates require validation',
            'Land use designation needs confirmation',
            'Title verification pending',
        ];

        // Randomly select 1-3 issues
        $numIssues = rand(1, 3);
        $selectedIssues = array_rand(array_flip($possibleIssues), $numIssues);
        
        return is_array($selectedIssues) ? $selectedIssues : [$selectedIssues];
    }

    /**
     * Show CSV import form
     */
    public function showImportForm()
    {
        return view('fileindexing.import');
    }

    public function previewCsv(Request $request)
    {
        try {
            set_time_limit(180);

            $request->validate([
                'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            ]);

            $uploadedFile = $request->file('csv');
            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

            $path = $uploadedFile->getRealPath();
            $handle = fopen($path, 'r');
            if ($handle === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to open uploaded file.',
                ], 422);
            }

            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'CSV file is empty.',
                ], 422);
            }

            $normalize = function ($value) {
                return strtolower(preg_replace('/\s+/', '', (string) $value));
            };

            $headerMap = [];
            foreach ($header as $index => $column) {
                $headerMap[$normalize($column)] = $index;
            }

            $requiredColumns = ['sn', 'registry', 'batchno', 'filenumber', 'filetitle', 'landuse', 'plotnumber', 'lpknno', 'tpno', 'district', 'lga'];
            foreach ($requiredColumns as $required) {
                if (!array_key_exists($required, $headerMap)) {
                    fclose($handle);
                    return response()->json([
                        'success' => false,
                        'message' => "Missing required column: {$required}",
                    ], 422);
                }
            }

            $csvData = [];
            $rowNumber = 1;
            $allFileNumbers = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                $csvData[] = [
                    'row_number' => $rowNumber,
                    'row' => $row,
                ];

                $fileNumber = trim((string) ($row[$headerMap['filenumber']] ?? ''));
                if ($fileNumber !== '') {
                    $allFileNumbers[] = $fileNumber;
                }
            }
            fclose($handle);

            $uniqueFileNumbers = array_values(array_unique($allFileNumbers));

            $existingCounts = [];
            $existingDetails = collect();

            if (!empty($uniqueFileNumbers)) {
                // Use chunked queries to avoid SQL Server's 2100 parameter limit
                $countQuery = FileIndexing::on('sqlsrv')
                    ->select('file_number', DB::raw('COUNT(*) as occurrences'))
                    ->groupBy('file_number');
                
                $countResults = $this->chunkedWhereIn($countQuery, 'file_number', $uniqueFileNumbers);
                $existingCounts = $countResults->pluck('occurrences', 'file_number')->toArray();

                if (!empty($existingCounts)) {
                    $detailQuery = FileIndexing::on('sqlsrv')
                        ->select('id', 'file_number', 'file_title', 'registry', 'batch_no', 'tracking_id', 'updated_at')
                        ->orderBy('updated_at', 'desc');
                    
                    $existingDetails = $this->chunkedWhereIn($detailQuery, 'file_number', array_keys($existingCounts))
                        ->groupBy('file_number');
                }
            }

            $serialCounters = [];
            $seenInFile = [];
            $systemDuplicates = [];
            $csvDuplicates = [];
            $importableRows = [];
            $errors = 0;
            $overflow = [];
            $totalDuplicates = 0;

            foreach ($csvData as $record) {
                $row = $record['row'];
                $currentRowNumber = $record['row_number'];

                try {
                    $registry = trim((string) ($row[$headerMap['registry']] ?? ''));
                    $batchNo = (int) ($row[$headerMap['batchno']] ?? 0);
                    $fileNumber = trim((string) ($row[$headerMap['filenumber']] ?? ''));
                    $fileTitle = trim((string) ($row[$headerMap['filetitle']] ?? ''));

                    if ($batchNo <= 0 || $fileNumber === '' || $fileTitle === '') {
                        $errors++;
                        continue;
                    }

                    $batchKey = "{$registry}|{$batchNo}";
                    $serialCount = $serialCounters[$batchKey] ?? 0;

                    if (isset($existingCounts[$fileNumber])) {
                        $totalDuplicates++;

                        if (!isset($systemDuplicates[$fileNumber])) {
                            $systemDuplicates[$fileNumber] = [
                                'file_number' => $fileNumber,
                                'registry' => $registry,
                                'batch_no' => $batchNo,
                                'file_title' => $fileTitle,
                                'csv_occurrences' => 0,
                                'system_occurrences' => (int) $existingCounts[$fileNumber],
                                'rows' => [],
                            ];
                        }

                        if (count($systemDuplicates[$fileNumber]['rows']) < self::MAX_DUPLICATE_RECORDS) {
                            $systemDuplicates[$fileNumber]['rows'][] = [
                                'row_number' => $currentRowNumber,
                                'registry' => $registry,
                                'batch_no' => $batchNo,
                                'file_title' => $fileTitle,
                            ];
                        }

                        $systemDuplicates[$fileNumber]['csv_occurrences']++;
                        continue;
                    }

                    if (isset($seenInFile[$fileNumber])) {
                        $totalDuplicates++;

                        if (!isset($csvDuplicates[$fileNumber])) {
                            $csvDuplicates[$fileNumber] = [
                                'file_number' => $fileNumber,
                                'first_occurrence' => $seenInFile[$fileNumber],
                                'duplicates' => [],
                            ];
                        }

                        if (count($csvDuplicates[$fileNumber]['duplicates']) < self::MAX_DUPLICATE_RECORDS) {
                            $csvDuplicates[$fileNumber]['duplicates'][] = [
                                'row_number' => $currentRowNumber,
                                'registry' => $registry,
                                'batch_no' => $batchNo,
                                'file_title' => $fileTitle,
                                'differences' => $this->diffCsvRows($header, $seenInFile[$fileNumber]['raw_row'], $row),
                            ];
                        }
                        continue;
                    }

                    if ($serialCount >= self::MAX_BATCH_ENTRIES) {
                        $overflow[$batchKey] = ($overflow[$batchKey] ?? 0) + 1;
                        $errors++;
                        continue;
                    }

                    $serialCounters[$batchKey] = $serialCount + 1;

                    $seenInFile[$fileNumber] = [
                        'row_number' => $currentRowNumber,
                        'registry' => $registry,
                        'batch_no' => $batchNo,
                        'file_title' => $fileTitle,
                        'raw_row' => $row,
                    ];

                    $importableRows[] = $row;
                } catch (\Throwable $inner) {
                    Log::warning('CSV preview row error', [
                        'row_number' => $currentRowNumber,
                        'error' => $inner->getMessage(),
                    ]);
                    $errors++;
                }
            }

            $systemDuplicates = array_map(function (array $entry) use ($existingDetails) {
                $existingRecords = collect();
                if ($existingDetails instanceof \Illuminate\Support\Collection && $existingDetails->has($entry['file_number'])) {
                    $existingRecords = $existingDetails[$entry['file_number']];
                }

                $entry['existing_total_records'] = $existingRecords->count();
                $entry['existing_records'] = $existingRecords
                    ->take(5)
                    ->map(function ($record) {
                        return [
                            'id' => $record->id,
                            'file_title' => $record->file_title,
                            'registry' => $record->registry,
                            'batch_no' => $record->batch_no,
                            'tracking_id' => $record->tracking_id,
                            'updated_at' => optional($record->updated_at)->format('Y-m-d H:i:s'),
                        ];
                    })
                    ->values()
                    ->toArray();

                $entry['rows'] = array_values($entry['rows']);

                return $entry;
            }, array_values($systemDuplicates));

            $csvDuplicates = array_map(function (array $entry) {
                $entry['duplicates'] = array_values($entry['duplicates']);
                return $entry;
            }, array_values($csvDuplicates));

            $overflowList = [];
            if (!empty($overflow)) {
                foreach ($overflow as $key => $count) {
                    [$registry, $batchNo] = explode('|', $key, 2);
                    $overflowList[] = [
                        'registry' => $registry,
                        'batch_no' => $batchNo,
                        'count' => $count,
                    ];
                }
            }

            $cleanCsvMeta = $this->storeCleanCsvFile($header, $importableRows, $originalName);

            return response()->json([
                'success' => true,
                'summary' => [
                    'total_rows' => count($csvData),
                    'importable' => count($importableRows),
                    'system_duplicates' => count($systemDuplicates),
                    'csv_duplicates' => count($csvDuplicates),
                    'duplicates' => $totalDuplicates,
                    'errors' => $errors,
                ],
                'system_duplicates' => $systemDuplicates,
                'csv_duplicates' => $csvDuplicates,
                'overflow' => $overflowList,
                'clean_csv' => $cleanCsvMeta,
                'headers' => array_map(function ($column) {
                    return trim((string) $column);
                }, $header),
            ]);
        } catch (\Throwable $e) {
            Log::error('CSV preview error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze CSV file. Please try again or contact support.',
            ], 500);
        }
    }

    protected function diffCsvRows(array $header, array $firstRow, array $comparisonRow): array
    {
        $differences = [];

        foreach ($header as $index => $column) {
            $firstValue = trim((string) ($firstRow[$index] ?? ''));
            $secondValue = trim((string) ($comparisonRow[$index] ?? ''));

            if ($firstValue !== $secondValue) {
                $differences[] = [
                    'column' => $column !== null && $column !== '' ? $column : 'Column ' . ($index + 1),
                    'first_value' => $firstValue,
                    'second_value' => $secondValue,
                ];
            }
        }

        return $differences;
    }

    private function storeCleanCsvFile(array $header, array $rows, string $originalName): ?array
    {
        if (empty($rows)) {
            return null;
        }

        try {
            $directory = self::CLEAN_CSV_STORAGE_DIR;
            
            // Ensure directory exists using Storage facade
            if (!Storage::disk('local')->exists($directory)) {
                Storage::disk('local')->makeDirectory($directory);
            }

            $token = (string) Str::uuid();
            $filename = "{$token}.csv";
            $relativePath = "{$directory}/{$filename}";
            
            // Create CSV content as string
            $csvContent = '';
            $handle = fopen('php://temp', 'r+');
            
            // Write header
            fputcsv($handle, $header);
            
            // Write rows
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            
            // Get content
            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);
            
            // Store using Laravel Storage
            $success = Storage::disk('local')->put($relativePath, $csvContent);
            
            if (!$success) {
                Log::error('Failed to store clean CSV file', [
                    'path' => $relativePath,
                    'directory' => $directory
                ]);
                return null;
            }

            $downloadName = Str::slug($originalName ?: 'file-indexing') . '-cleaned-' . now()->format('Ymd-His') . '.csv';

            // Cache file info for download
            Cache::put(
                $this->getCleanCsvCacheKey($token),
                [
                    'path' => $relativePath,
                    'download_name' => $downloadName,
                    'row_count' => count($rows),
                ],
                now()->addMinutes(self::CLEAN_CSV_TTL_MINUTES)
            );

            return [
                'token' => $token,
                'download_name' => $downloadName,
                'row_count' => count($rows),
            ];
        } catch (\Exception $e) {
            Log::error('Exception in storeCleanCsvFile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function getCleanCsvCacheKey(string $token): string
    {
        return self::CLEAN_CSV_CACHE_PREFIX . $token;
    }

    public function downloadCleanCsv(string $token)
    {
        $cacheKey = $this->getCleanCsvCacheKey($token);
        $meta = Cache::get($cacheKey);

        if (!$meta || empty($meta['path']) || empty($meta['download_name'])) {
            abort(404, 'Clean CSV download has expired or is unavailable.');
        }

        $path = $meta['path'];
        if (!Storage::disk('local')->exists($path)) {
            Cache::forget($cacheKey);
            abort(404, 'Clean CSV file could not be found.');
        }

        return response()->streamDownload(function () use ($path) {
            $stream = Storage::disk('local')->readStream($path);
            if ($stream === false) {
                return;
            }
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $meta['download_name'], [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function getDatabaseDuplicates(Request $request)
    {
        $perPage = (int) min(max($request->input('per_page', 10), 5), 100);
        $search = trim((string) $request->input('search', ''));
        $minOccurrences = max((int) $request->input('min_occurrences', 2), 2);
        $sortBy = $request->input('sort_by', 'occurrences');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $baseQuery = FileIndexing::on('sqlsrv');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $baseQuery->where(function ($query) use ($like) {
                $query->where('file_number', 'like', $like)
                    ->orWhere('file_title', 'like', $like)
                    ->orWhere('registry', 'like', $like)
                    ->orWhere('batch_no', 'like', $like)
                    ->orWhere('tracking_id', 'like', $like);
            });
        }

        $duplicateQuery = $baseQuery
            ->select('file_number')
            ->selectRaw('COUNT(*) as occurrences')
            ->selectRaw('MAX(updated_at) as latest_updated_at')
            ->selectRaw('MAX(created_at) as latest_created_at')
            ->groupBy('file_number')
            ->havingRaw('COUNT(*) >= ?', [$minOccurrences]);

        $sortColumn = match ($sortBy) {
            'file_number' => 'file_number',
            'latest_updated_at' => 'latest_updated_at',
            default => 'occurrences',
        };

        $duplicateQuery->orderBy($sortColumn, $sortDir);

        $paginator = $duplicateQuery->paginate($perPage);
        $items = collect($paginator->items());

        $fileNumbers = $items->pluck('file_number')->filter()->unique()->values();
        $latestRecords = collect();

        if ($fileNumbers->isNotEmpty()) {
            $latestRecords = FileIndexing::on('sqlsrv')
                ->select('id', 'file_number', 'file_title', 'registry', 'batch_no', 'tracking_id', 'updated_at')
                ->whereIn('file_number', $fileNumbers->all())
                ->orderBy('updated_at', 'desc')
                ->get()
                ->groupBy('file_number')
                ->map(function ($group) {
                    return $group->first();
                });
        }

        $payload = $items->map(function ($item) use ($latestRecords) {
            $record = $latestRecords->get($item->file_number);
            $latestDate = $record && $record->updated_at ? $record->updated_at->format('Y-m-d H:i:s') : null;

            if (!$latestDate && !empty($item->latest_updated_at)) {
                try {
                    $latestDate = Carbon::parse($item->latest_updated_at)->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                    $latestDate = (string) $item->latest_updated_at;
                }
            }

            return [
                'file_number' => $item->file_number,
                'occurrences' => (int) $item->occurrences,
                'latest_updated_at' => $latestDate,
                'file_title' => $record->file_title ?? null,
                'registry' => $record->registry ?? null,
                'batch_no' => $record->batch_no ?? null,
                'tracking_id' => $record->tracking_id ?? null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $payload,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'filters' => [
                'min_occurrences' => $minOccurrences,
                'sort_by' => $sortColumn,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    public function getDatabaseDuplicateDetails(Request $request)
    {
        $validated = $request->validate([
            'file_number' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $fileNumber = $validated['file_number'];
        $limit = (int) ($validated['limit'] ?? 100);

        $records = FileIndexing::on('sqlsrv')
            ->where('file_number', $fileNumber)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->id,
                    'file_title' => $record->file_title,
                    'registry' => $record->registry,
                    'batch_no' => $record->batch_no,
                    'tracking_id' => $record->tracking_id,
                    'updated_at' => optional($record->updated_at)->format('Y-m-d H:i:s'),
                    'created_at' => optional($record->created_at)->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'file_number' => $fileNumber,
            'count' => $records->count(),
            'records' => $records,
            'limit' => $limit,
        ]);
    }

    public function exportDatabaseDuplicates(Request $request)
    {
        set_time_limit(300);

        $search = trim((string) $request->input('search', ''));
        $minOccurrences = max((int) $request->input('min_occurrences', 2), 2);

        $baseQuery = FileIndexing::on('sqlsrv');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $baseQuery->where(function ($query) use ($like) {
                $query->where('file_number', 'like', $like)
                    ->orWhere('file_title', 'like', $like)
                    ->orWhere('registry', 'like', $like)
                    ->orWhere('batch_no', 'like', $like)
                    ->orWhere('tracking_id', 'like', $like);
            });
        }

        $duplicates = $baseQuery
            ->select('file_number')
            ->selectRaw('COUNT(*) as occurrences')
            ->selectRaw('MAX(updated_at) as latest_updated_at')
            ->groupBy('file_number')
            ->havingRaw('COUNT(*) >= ?', [$minOccurrences])
            ->orderBy('occurrences', 'desc')
            ->get();
        $latestRecords = collect();

        if ($duplicates->isNotEmpty()) {
            $fileNumbers = $duplicates->pluck('file_number')->all();
            
            $detailQuery = FileIndexing::on('sqlsrv')
                ->select('file_number', 'file_title', 'registry', 'batch_no', 'tracking_id', 'updated_at')
                ->orderBy('updated_at', 'desc');
            
            $latestRecords = $this->chunkedWhereIn($detailQuery, 'file_number', $fileNumbers)
                ->groupBy('file_number');
        }

        $filename = 'database-duplicates-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($duplicates, $latestRecords) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['File Number', 'Occurrences', 'Latest Title', 'Registry', 'Batch No', 'Tracking ID', 'Last Updated']);

            foreach ($duplicates as $duplicate) {
                $recordGroup = $latestRecords->get($duplicate->file_number, collect());
                $record = $recordGroup instanceof \Illuminate\Support\Collection ? $recordGroup->first() : null;
                $latestDate = $record && $record->updated_at ? $record->updated_at->format('Y-m-d H:i:s') : null;

                if (!$latestDate && !empty($duplicate->latest_updated_at)) {
                    try {
                        $latestDate = Carbon::parse($duplicate->latest_updated_at)->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        $latestDate = (string) $duplicate->latest_updated_at;
                    }
                }

                fputcsv($handle, [
                    $duplicate->file_number,
                    (int) $duplicate->occurrences,
                    $record->file_title ?? '',
                    $record->registry ?? '',
                    $record->batch_no ?? '',
                    $record->tracking_id ?? '',
                    $latestDate ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Create a file indexing record inside a transaction while reserving a batch slot.
     *
     * @return array{0: \App\Models\FileIndexing, 1: array{batch_no:int,batch_id:int,shelf_label_id:int,shelf_location:string,serial_no:int}}
     */
    private function createFileIndexingWithBatch(array $attributes): array
    {
        return DB::connection('sqlsrv')->transaction(function () use ($attributes) {
            // Check if batch information is provided from frontend (from auto-assignment preview)
            $hasBatchData = !empty($attributes['batch_no']) && !empty($attributes['shelf_location']);
            
            if ($hasBatchData) {
                // Use the batch data from frontend (already previewed)
                $assignment = [
                    'batch_no' => $attributes['batch_no'],
                    'serial_no' => $attributes['serial_no'] ?? 1,
                    'shelf_location' => $attributes['shelf_location'],
                    'shelf_label_id' => $attributes['shelf_label_id'] ?? $attributes['batch_no'],
                    'batch_id' => $attributes['batch_id'] ?? $attributes['batch_no'],
                ];
                
                Log::info('Using frontend batch assignment', [
                    'assignment' => $assignment,
                    'file_number' => $attributes['file_number'] ?? 'unknown'
                ]);
            } else {
                // Fallback to service assignment for bulk imports or legacy code
                $assignment = $this->batchService->assignNext();
                
                Log::info('Using service batch assignment', [
                    'assignment' => $assignment,
                    'file_number' => $attributes['file_number'] ?? 'unknown'
                ]);
            }

            $payload = $attributes;
            $payload['batch_no'] = $assignment['batch_no'];
            $payload['serial_no'] = $assignment['serial_no'];
            $payload['shelf_location'] = $assignment['shelf_location'];
            $payload['shelf_label_id'] = $assignment['shelf_label_id'];
            $payload['batch_id'] = $assignment['batch_id'] ?? null;

            $record = FileIndexing::on('sqlsrv')->create($payload);

            return [$record, $assignment];
        });
    }

    /**
     * Import CSV file into file_indexings table
     */
    /**
     * Generate a unique tracking ID for file indexing records
     * Format: TRK-{4 chars}-{8 chars}
     * Example: TRK-K463-N39NYWCC5
     */
    private function generateTrackingId(): string
    {
        // Characters excluding confusing ones (0, O, I, 1, etc.)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        
        $part1 = '';
        for ($i = 0; $i < 4; $i++) {
            $part1 .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        $part2 = '';
        for ($i = 0; $i < 8; $i++) {
            $part2 .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return "TRK-{$part1}-{$part2}";
    }

    public function importCsv(Request $request)
    {
        $startTime = microtime(true);
        // Increase execution time for large imports
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M'); // Increase memory limit

        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $request->file('csv')->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('error', 'Unable to open uploaded file.');
        }

        // Normalize headers
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty.');
        }

        $normalize = fn($s) => strtolower(preg_replace('/\s+/', '', (string)$s));
        $headerMap = [];
        foreach ($header as $i => $col) {
            $headerMap[$normalize($col)] = $i;
        }

        $required = ['sn','registry','batchno','filenumber','filetitle','landuse','plotnumber','lpknno','tpno','district','lga'];
        foreach ($required as $req) {
            if (!array_key_exists($req, $headerMap)) {
                fclose($handle);
                return back()->with('error', "Missing required column: {$req}");
            }
        }

        // Pre-load existing file numbers for faster duplicate checking
        $allFileNumbers = [];

        // First pass: collect all rows with their original index
        $csvData = [];
        $rowNo = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNo++;
            $fileNo = trim((string)($row[$headerMap['filenumber']] ?? ''));

            if ($fileNo !== '') {
                $allFileNumbers[] = $fileNo;
            }

            $csvData[] = [
                'row_number' => $rowNo,
                'row' => $row,
            ];
        }
        fclose($handle);

        // Pre-load existing records to avoid individual queries
        $existingRecords = [];
        if (!empty($allFileNumbers)) {
            $uniqueFileNumbers = array_values(array_unique($allFileNumbers));
            
            $query = FileIndexing::on('sqlsrv')->select('file_number');
            $results = $this->chunkedWhereIn($query, 'file_number', $uniqueFileNumbers);
            
            $existingRecords = $results->pluck('file_number')->mapWithKeys(function ($fileNumber) {
                return [$fileNumber => true];
            })->toArray();
        }

    $imported = 0;
        $duplicates = 0;
        $errors = 0;
        $duplicateRows = [];
        $seenInFile = [];
        $duplicatesTruncated = false;

        $userId = Auth::id();

        // Process the collected CSV data
        foreach ($csvData as $record) {
            $row = $record['row'];
            $currentRowNo = $record['row_number'];

            try {
                $registry = trim((string)($row[$headerMap['registry']] ?? ''));
                $batchNo  = (int)($row[$headerMap['batchno']] ?? 0);
                $fileNo   = trim((string)($row[$headerMap['filenumber']] ?? ''));
                $fileTitle= trim((string)($row[$headerMap['filetitle']] ?? ''));
                $landUse  = trim((string)($row[$headerMap['landuse']] ?? 'Residential'));
                $plotNo   = trim((string)($row[$headerMap['plotnumber']] ?? ''));
                $lpknNo   = trim((string)($row[$headerMap['lpknno']] ?? ''));
                $tpNo     = trim((string)($row[$headerMap['tpno']] ?? ''));
                $district = trim((string)($row[$headerMap['district']] ?? ''));
                $lga      = trim((string)($row[$headerMap['lga']] ?? ''));

                if ($batchNo <= 0 || $fileNo === '' || $fileTitle === '') {
                    $errors++;
                    continue;
                }

                // Fast duplicate check using file number only (system + current upload)
                if (isset($existingRecords[$fileNo])) {
                    $duplicates++;
                    if (count($duplicateRows) < self::MAX_DUPLICATE_RECORDS) {
                        $duplicateRows[] = [
                            'row_number' => $currentRowNo,
                            'file_number' => $fileNo,
                            'registry' => $registry,
                            'batch_no' => $batchNo,
                            'file_title' => $fileTitle,
                            'reason' => 'Already exists in the system',
                        ];
                    } else {
                        $duplicatesTruncated = true;
                    }
                    continue;
                }

                if (isset($seenInFile[$fileNo])) {
                    $duplicates++;
                    $firstSeenRow = $seenInFile[$fileNo]['row_number'] ?? null;
                    if (count($duplicateRows) < self::MAX_DUPLICATE_RECORDS) {
                        $duplicateRows[] = [
                            'row_number' => $currentRowNo,
                            'file_number' => $fileNo,
                            'registry' => $registry,
                            'batch_no' => $batchNo,
                            'file_title' => $fileTitle,
                            'reason' => $firstSeenRow ? "Duplicate in uploaded CSV (first seen at row {$firstSeenRow})" : 'Duplicate in uploaded CSV',
                        ];
                    } else {
                        $duplicatesTruncated = true;
                    }
                    continue;
                }

                // Track the file number to prevent CSV duplicates
                $seenInFile[$fileNo] = [
                    'row_number' => $currentRowNo,
                    'registry' => $registry,
                    'batch_no' => $batchNo, // Keep for duplicate tracking reference
                ];

                try {
                    // Let createFileIndexingWithBatch handle proper batch assignment
                    // Don't pass CSV batch_no or serial_no - use service assignment instead
                    [$fileIndexing, $assignment] = $this->createFileIndexingWithBatch([
                        'file_number'      => $fileNo,
                        'file_title'       => $fileTitle,
                        'land_use_type'    => $landUse ?: 'Residential',
                        'plot_number'      => $plotNo ?: null,
                        'tp_no'            => $tpNo ?: null,
                        'lpkn_no'          => $lpknNo ?: null,
                        'district'         => $district ?: null,
                        'lga'              => $lga ?: null,
                        'registry'         => $registry ?: null,
                        'has_cofo'         => 0,
                        'is_merged'        => 0,
                        'has_transaction'  => 0,
                        'is_problematic'   => 0,
                        'is_co_owned_plot' => 0,
                        'is_updated'       => 0,
                        'workflow_status'  => 'indexed',
                        'tracking_id'      => $this->generateTrackingId(),
                        'created_by'       => $userId,
                        'updated_by'       => $userId,
                        // Note: batch_no, serial_no, shelf_location will be assigned by the service
                    ]);

                    $imported++;

                    if ($imported % 500 === 0) {
                        gc_collect_cycles();
                    }
                } catch (RuntimeException $runtimeException) {
                    $errors++;
                    Log::warning('CSV import batch assignment failed', [
                        'row_number' => $currentRowNo,
                        'file_number' => $fileNo,
                        'error' => $runtimeException->getMessage(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("CSV import error at row {$currentRowNo}: " . $e->getMessage());
                $errors++;
            }
        }

        $processingTime = round(microtime(true) - $startTime, 2);

        $summary = [
            'total_rows' => count($csvData),
            'imported' => $imported,
            'duplicates' => $duplicates,
            'errors' => $errors,
            'processing_time' => $processingTime,
            'overflow' => [], // Batch service handles capacity management
            'duplicates_truncated' => $duplicatesTruncated,
        ];

        $overflowMsg = '';
        if (!empty($summary['overflow'])) {
            $pairs = array_map(function ($entry) {
                return "Registry={$entry['registry']}, BatchNo={$entry['batch_no']} (+{$entry['overflow']} overflow)";
            }, $summary['overflow']);
            $overflowMsg = ' | Overflow skipped: ' . implode('; ', $pairs);
        }

        return back()->with([
            'success' => "Import completed. Imported: {$imported}, Duplicates: {$duplicates}, Errors: {$errors}{$overflowMsg}",
            'import_summary' => $summary,
            'duplicate_records' => $duplicateRows,
        ]);
    }

    /**
     * Get batch tracking history (API endpoint)
     */
    public function getBatchHistory(Request $request)
    {
        try {
            $search = $request->get('search', '');
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 10);

            $query = \App\Models\TrackingSheet::on('sqlsrv')
                ->with(['generatedBy', 'lastPrintedBy'])
                ->orderBy('generated_at', 'desc');

            // Apply search filter
            if (!empty($search)) {
                $query->search($search);
            }

            // Get total count before pagination
            $total = $query->count();

            // Apply pagination
            $batches = $query->skip(($page - 1) * $perPage)
                           ->take($perPage)
                           ->get()
                           ->map(function ($batch) {
                               return [
                                   'batch_id' => $batch->batch_id,
                                   'batch_name' => $batch->batch_name,
                                   'file_count' => $batch->file_count,
                                   'batch_type' => $batch->batch_type,
                                   'status' => $batch->status,
                                   'print_count' => $batch->print_count,
                                   'generated_at' => $batch->generated_at->format('M d, Y g:i A'),
                                   'generated_by_name' => $batch->generatedBy->name ?? 'Unknown',
                                   'last_printed_at' => $batch->last_printed_at ? $batch->last_printed_at->format('M d, Y g:i A') : null,
                                   'last_printed_by_name' => $batch->lastPrintedBy->name ?? null,
                                   'notes' => $batch->notes
                               ];
                           });

            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => (($page - 1) * $perPage) + 1,
                'to' => min($page * $perPage, $total)
            ];

            return response()->json([
                'success' => true,
                'batches' => $batches,
                'pagination' => $pagination
            ]);

        } catch (Exception $e) {
            Log::error('Error getting batch history', [
                'error' => $e->getMessage(),
                'search' => $request->get('search'),
                'page' => $request->get('page')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading batch history: ' . $e->getMessage(),
                'batches' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 10,
                    'total' => 0,
                    'last_page' => 1,
                    'from' => 0,
                    'to' => 0
                ]
            ]);
        }
    }

    /**
     * Save batch tracking history when generating tracking sheets
     */
    public function saveBatchTrackingHistory($fileIds, $batchType = 'manual', $batchName = null)
    {
        try {
            $fileIdsArray = is_array($fileIds) ? $fileIds : explode(',', $fileIds);
            $fileCount = count($fileIdsArray);
            
            $batchId = \App\Models\TrackingSheet::generateBatchId($batchType);
            
            if (!$batchName) {
                $batchName = match($batchType) {
                    'auto_100' => "Auto Batch 100 Files - " . now()->format('M d, Y g:i A'),
                    'auto_200' => "Auto Batch 200 Files - " . now()->format('M d, Y g:i A'),
                    default => "Manual Batch {$fileCount} Files - " . now()->format('M d, Y g:i A')
                };
            }

            $trackingSheet = \App\Models\TrackingSheet::create([
                'batch_id' => $batchId,
                'batch_name' => $batchName,
                'file_count' => $fileCount,
                'selected_file_ids' => json_encode($fileIdsArray),
                'generated_by' => auth()->id(),
                'generated_at' => now(),
                'batch_type' => $batchType,
                'status' => 'generated',
                'print_count' => 1, // Counting the initial generation as first print
                'last_printed_at' => now(),
                'last_printed_by' => auth()->id()
            ]);

            // Update file_indexings table with batch tracking information
            try {
                FileIndexing::on('sqlsrv')
                    ->whereIn('id', $fileIdsArray)
                    ->update([
                        'batch_generated' => 1,
                        'last_batch_id' => $batchId,
                        'batch_generated_at' => now(),
                        'batch_generated_by' => auth()->id()
                    ]);

                Log::info('File indexings updated with batch information', [
                    'batch_id' => $batchId,
                    'file_ids' => $fileIdsArray,
                    'updated_count' => count($fileIdsArray)
                ]);

            } catch (Exception $e) {
                Log::error('Error updating file_indexings with batch information', [
                    'batch_id' => $batchId,
                    'file_ids' => $fileIdsArray,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Batch tracking history saved', [
                'batch_id' => $batchId,
                'file_count' => $fileCount,
                'batch_type' => $batchType,
                'user_id' => auth()->id()
            ]);

            return $trackingSheet;

        } catch (Exception $e) {
            Log::error('Error saving batch tracking history', [
                'file_ids' => $fileIds,
                'batch_type' => $batchType,
                'error' => $e->getMessage()
            ]);

            // Don't throw exception - we don't want to break the tracking sheet generation
            // if saving history fails
            return null;
        }
    }

    /**
     * Format batch_generated_at field safely
     */
    private function formatBatchGeneratedAt($batchGeneratedAt)
    {
        if (!$batchGeneratedAt) {
            return null;
        }

        try {
            // If it's already a Carbon instance, format it
            if ($batchGeneratedAt instanceof Carbon) {
                return $batchGeneratedAt->format('M d, Y g:i A');
            }
            
            // If it's a string, parse it with Carbon first
            if (is_string($batchGeneratedAt)) {
                return Carbon::parse($batchGeneratedAt)->format('M d, Y g:i A');
            }
            
            // If it's something else, try to convert to string
            return (string) $batchGeneratedAt;
        } catch (Exception $e) {
            // If all else fails, return the original value as string
            return (string) $batchGeneratedAt;
        }
    }

    private function resolveDocumentTypeFromValues(?string $landUseType, bool $hasCofo): string
    {
        if ($hasCofo) {
            return 'Certificate of Occupancy';
        }

        $normalized = strtolower(trim((string) $landUseType));

        switch ($normalized) {
            case 'commercial':
                return 'Commercial Document';
            case 'industrial':
                return 'Industrial Document';
            default:
                return 'Property Document';
        }
    }

    private function escapeLikePattern(string $value): string
    {
        return str_replace(['[', '%', '_'], ['[[]', '[%]', '[_]'], $value);
    }

    /**
     * API endpoint to get file IDs for a specific batch
     */
    public function getBatchFileIds($batchId)
    {
        try {
            $trackingSheet = \App\Models\TrackingSheet::where('batch_id', $batchId)->first();
            
            if (!$trackingSheet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch not found'
                ], 404);
            }
            
            $fileIds = json_decode($trackingSheet->selected_file_ids, true);
            
            if (empty($fileIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file IDs found in batch'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'file_ids' => $fileIds,
                'batch_info' => [
                    'batch_id' => $trackingSheet->batch_id,
                    'batch_name' => $trackingSheet->batch_name,
                    'file_count' => $trackingSheet->file_count,
                    'print_count' => $trackingSheet->print_count
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Error getting batch file IDs', [
                'batch_id' => $batchId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving batch file IDs'
            ], 500);
        }
    }

    /**
     * Get available batches that haven't been generated yet
     */
    public function getAvailableBatches()
    {
        try {
            $search = trim((string) request()->get('search', ''));
            $page = (int) request()->get('page', 1);
            $perPage = (int) request()->get('per_page', 0);

            $payload = FileindexingBatch::availableForSelection($search, $page, $perPage);

            return response()->json(array_merge([
                'success' => true,
            ], $payload));
        } catch (Exception $e) {
            Log::error('Error getting available batches', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving available batches'
            ], 500);
        }
    }

    /**
     * Get all batches for export purposes (includes full batches)
     */
    public function getAllBatchesForExport()
    {
        try {
            $search = trim((string) request()->get('q', ''));
            $page = (int) request()->get('page', 1);
            $perPage = (int) request()->get('per_page', 20);

            // Get all distinct batch numbers from file_indexings table with counts
            $query = DB::connection('sqlsrv')
                ->table('file_indexings')
                ->select(
                    'batch_no',
                    DB::raw('COUNT(*) as file_count'),
                    DB::raw('MAX(shelf_location) as shelf_location')
                )
                ->where(function ($q) {
                    $q->where('is_deleted', 0)
                      ->orWhereNull('is_deleted');
                })
                ->whereNotNull('batch_no');

            // Apply search filter if provided
            if (!empty($search)) {
                $query->where('batch_no', 'LIKE', '%' . $search . '%');
            }

            $query->groupBy('batch_no')
                  ->orderByRaw('CAST(batch_no AS INT) DESC'); // Proper numeric sorting

            // Get total count for pagination
            $totalQuery = clone $query;
            $total = $totalQuery->get()->count();

            // Apply pagination
            $offset = ($page - 1) * $perPage;
            $batches = $query->skip($offset)->take($perPage)->get();

            // Format the results
            $formattedBatches = $batches->map(function ($batch) {
                return [
                    'id' => $batch->batch_no,
                    'text' => $batch->batch_no . ' (' . $batch->file_count . ' files)',
                    'file_count' => $batch->file_count,
                    'shelf_location' => $batch->shelf_location
                ];
            });

            return response()->json([
                'success' => true,
                'batches' => $formattedBatches,
                'pagination' => [
                    'more' => ($page * $perPage) < $total,
                    'current_page' => $page,
                    'total' => $total,
                    'per_page' => $perPage
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error getting all batches for export', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving batches for export'
            ], 500);
        }
    }

    /**
     * Get current batch status from actual file_indexings data
     */
    public function getCurrentBatchStatus()
    {
        try {
            // Get the highest batch number first (handle string sorting properly)
            $lastBatchNo = DB::connection('sqlsrv')
                ->table('file_indexings')
                ->where(function ($query) {
                    $query->where('is_deleted', 0)
                          ->orWhereNull('is_deleted');
                })
                ->whereNotNull('batch_no')
                ->orderByRaw('CAST(batch_no AS INT) DESC')
                ->value('batch_no');

            if (!$lastBatchNo) {
                // No batches exist yet
                return response()->json([
                    'success' => true,
                    'will_create_new' => true,
                    'next_batch_no' => 1,
                    'next_shelf_location' => 'A1',
                    'next_shelf_label_id' => 1,
                    'next_batch_id' => 1,
                ]);
            }

            // Get all records in the last batch to determine the most common shelf info
            $batchRecords = DB::connection('sqlsrv')
                ->table('file_indexings')
                ->where('batch_no', $lastBatchNo)
                ->where(function ($query) {
                    $query->where('is_deleted', 0)
                          ->orWhereNull('is_deleted');
                })
                ->get();

            $totalCount = $batchRecords->count();

            if ($totalCount >= 100) {
                // Current batch is full, need to create new one
                $nextBatchNo = $lastBatchNo + 1;
                $nextShelfLocation = $this->calculateNextShelfLocation($nextBatchNo);
                
                return response()->json([
                    'success' => true,
                    'will_create_new' => true,
                    'next_batch_no' => $nextBatchNo,
                    'next_shelf_location' => $nextShelfLocation,
                    'next_shelf_label_id' => $nextBatchNo,
                    'next_batch_id' => $nextBatchNo,
                ]);
            }

            // Find the most common shelf location and label ID in this batch
            $shelfGroups = $batchRecords->groupBy(function($record) {
                return $record->shelf_location . '|' . $record->shelf_label_id;
            });

            $mostCommonShelf = $shelfGroups->sortByDesc(function($group) {
                return $group->count();
            })->first();

            // Always use calculated shelf location for consistency
            // This ensures batch 35 shows A37 regardless of what's stored in individual records
            $shelfLocation = $this->calculateCurrentShelfLocation($lastBatchNo);
            $shelfLabelId = $lastBatchNo + 2; // Pattern: batch 35 = label 37
            
            // Log for debugging
            Log::info('Calculated shelf location for current batch', [
                'batch_no' => $lastBatchNo,
                'calculated_shelf_location' => $shelfLocation,
                'calculated_shelf_label_id' => $shelfLabelId,
                'stored_shelf_location' => $mostCommonShelf->first()->shelf_location ?? 'none',
                'stored_shelf_label_id' => $mostCommonShelf->first()->shelf_label_id ?? 'none',
            ]);

            // Current batch is not full, return its status
            return response()->json([
                'success' => true,
                'current_batch' => [
                    'batch_no' => $lastBatchNo,
                    'current_count' => $totalCount,
                    'shelf_location' => $shelfLocation,
                    'shelf_label_id' => $shelfLabelId,
                    'batch_id' => $lastBatchNo,
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Error getting current batch status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving current batch status'
            ], 500);
        }
    }

    /**
     * Calculate the next shelf location based on batch number
     * Uses same pattern as calculateCurrentShelfLocation for consistency
     */
    private function calculateNextShelfLocation($batchNo)
    {
        // Use the same calculation as current batches for consistency
        return $this->calculateCurrentShelfLocation($batchNo);
    }

    /**
     * Calculate the current shelf location for an existing batch
     * Based on pattern: Batch 35 = A37 (batch_no + 2)
     */
    private function calculateCurrentShelfLocation($batchNo)
    {
        // Pattern observed: batch 35 should show A37
        $shelfNumber = $batchNo + 2;
        
        // Each letter has 100 shelves (A1-A100, B1-B100, etc.)
        $letterIndex = intval(($shelfNumber - 1) / 100);
        $numberWithinLetter = (($shelfNumber - 1) % 100) + 1;
        
        $letter = chr(ord('A') + $letterIndex);
        return $letter . $numberWithinLetter;
    }

    /**
     * Get all files in a specific batch
     */
    public function getBatchFiles($batchNo)
    {
        try {
            $files = FileIndexing::on('sqlsrv')
                ->with(['mainApplication', 'scannings', 'pagetypings'])
                ->where('batch_no', $batchNo)
                ->where(function ($query) {
                    $query->where('batch_generated', 0)
                          ->orWhereNull('batch_generated');
                })
                ->where(function ($query) {
                    $query->where('is_deleted', 0)
                          ->orWhereNull('is_deleted');
                })
                ->get()
                ->map(function ($fi) {
                    $scannedCount = $fi->scannings->count();
                    $typedCount = $fi->pagetypings->count();
                    
                    return [
                        'id' => $fi->id,
                        'file_number' => $fi->file_number,
                        'fileNumber' => $fi->file_number, // JavaScript compatibility
                        'file_title' => $fi->file_title ?: 'No Title',
                        'name' => $fi->file_title ?: 'No Title', // JavaScript compatibility
                        'tracking_id' => $fi->tracking_id, // Add tracking_id field
                        'plot_number' => $fi->plot_number,
                        'district' => $fi->district,
                        'lga' => $fi->lga,
                        'registry' => $fi->registry,
                        'batch_no' => $fi->batch_no,
                        'created_at' => $fi->created_at->format('M d, Y g:i A'),
                        'date' => $fi->created_at->format('M d, Y'), // JavaScript compatibility
                        'indexingDate' => $fi->created_at->format('M d, Y g:i A'), // JavaScript compatibility
                        'created_by' => $fi->creator ? $fi->creator->name : 'Unknown',
                        'tpNumber' => $fi->tp_no,
                        'lpknNumber' => $fi->lpkn_no,
                        'location' => $fi->location,
                        'scanning_count' => $scannedCount,
                        'page_typing_count' => $typedCount,
                        'is_merged' => (bool) $fi->is_merged,
                        'has_transaction' => (bool) $fi->has_transaction,
                        'is_problematic' => (bool) $fi->is_problematic,
                        'is_co_owned_plot' => (bool) $fi->is_co_owned_plot,
                        // Batch tracking fields
                        'batch_generated' => (bool) $fi->batch_generated,
                        'last_batch_id' => $fi->last_batch_id,
                        'batch_generated_at' => $this->formatBatchGeneratedAt($fi->batch_generated_at),
                        'batch_generated_by' => $fi->batch_generated_by,
                    ];
                });

            return response()->json([
                'success' => true,
                'files' => $files
            ]);
        } catch (Exception $e) {
            Log::error('Error getting batch files', [
                'batch_no' => $batchNo,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving batch files'
            ], 500);
        }
    }

    /**
     * Update batch tracking fields when generating tracking sheets
     */
    private function updateBatchTrackingFields($fileIndexings, $trackingSheet)
    {
        try {
            if (!$trackingSheet) {
                Log::warning('No tracking sheet provided for batch field updates');
                return;
            }

            $fileIds = $fileIndexings->pluck('id')->toArray();
            
            // Update all files in the batch to mark them as batch generated
            DB::connection('sqlsrv')->table('file_indexings')
                ->whereIn('id', $fileIds)
                ->update([
                    'batch_generated' => 1,
                    'last_batch_id' => $trackingSheet->batch_id,
                    'batch_generated_at' => now(),
                    'batch_generated_by' => auth()->id()
                ]);

            Log::info('Updated batch tracking fields', [
                'file_count' => count($fileIds),
                'batch_id' => $trackingSheet->batch_id,
                'user_id' => auth()->id()
            ]);

        } catch (Exception $e) {
            Log::error('Error updating batch tracking fields', [
                'error' => $e->getMessage(),
                'batch_id' => $trackingSheet->batch_id ?? 'unknown'
            ]);
            // Don't throw exception as this is not critical to the main flow
        }
    }

    /**
     * Export indexed files by batch number
     */
    public function exportByBatch($batchNo, Request $request)
    {
        try {
            $format = $request->get('format', 'excel');
            
            // Get files from the specified batch
            $files = DB::connection('sqlsrv')->table('file_indexings as f')
                ->leftJoin('users as u', 'u.id', '=', 'f.created_by')
                ->where('f.batch_no', $batchNo)
                ->orderBy('f.id')
                ->get([
                    'f.registry',
                    'f.batch_no',
                    'f.file_number',
                    'f.file_title',
                    'f.land_use_type',
                    'f.plot_number',
                    'f.lpkn_no',
                    'f.tp_no',
                    'f.district',
                    'f.lga',
                    'f.shelf_location',
                    DB::raw("CONVERT(varchar(19), f.created_at, 120) as indexed_date"),
                    DB::raw("COALESCE(u.first_name + ' ' + u.last_name, '') as indexed_by")
                ]);

            if ($files->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files found for this batch.'
                ], 404);
            }

            $filename = "indexed_files_batch_{$batchNo}_" . date('Y-m-d_H-i-s');

            if ($format === 'csv') {
                return $this->generateCsvExport($files, $filename . '.csv');
            } else {
                return $this->generateExcelExport($files, $filename . '.xlsx');
            }

        } catch (Exception $e) {
            Log::error('Error exporting files by batch', [
                'error' => $e->getMessage(),
                'batch_no' => $batchNo
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error exporting files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export indexed files by date range
     */
    public function exportByDate(Request $request)
    {
        try {
            $format = $request->get('format', 'excel');
            $fromDate = $request->get('from');
            $toDate = $request->get('to');

            if (!$fromDate || !$toDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'From and To dates are required.'
                ], 422);
            }

            // Get files within the specified date range
            $files = DB::connection('sqlsrv')->table('file_indexings as f')
                ->leftJoin('users as u', 'u.id', '=', 'f.created_by')
                ->whereDate('f.created_at', '>=', $fromDate)
                ->whereDate('f.created_at', '<=', $toDate)
                ->orderBy('f.created_at')
                ->get([
                    'f.registry',
                    'f.batch_no',
                    'f.file_number',
                    'f.file_title',
                    'f.land_use_type',
                    'f.plot_number',
                    'f.lpkn_no',
                    'f.tp_no',
                    'f.district',
                    'f.lga',
                    'f.shelf_location',
                    DB::raw("CONVERT(varchar(19), f.created_at, 120) as indexed_date"),
                    DB::raw("COALESCE(u.first_name + ' ' + u.last_name, '') as indexed_by")
                ]);

            if ($files->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files found for the specified date range.'
                ], 404);
            }

            $filename = "indexed_files_{$fromDate}_to_{$toDate}_" . date('Y-m-d_H-i-s');

            if ($format === 'csv') {
                return $this->generateCsvExport($files, $filename . '.csv');
            } else {
                return $this->generateExcelExport($files, $filename . '.xlsx');
            }

        } catch (Exception $e) {
            Log::error('Error exporting files by date', [
                'error' => $e->getMessage(),
                'from_date' => $fromDate ?? 'not set',
                'to_date' => $toDate ?? 'not set'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error exporting files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate CSV export
     */
    private function generateCsvExport($files, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($files) {
            $out = fopen('php://output', 'w');
            
            // CSV Headers - SN	Registry Batch No File Number File Title Landuse Plot Number LPKN No TP No District LGA Shelf Location
            fputcsv($out, [
                'SN',
                'Registry',
                'Batch No',
                'File Number',
                'File Title',
                'Landuse',
                'Plot Number',
                'LPKN No',
                'TP No',
                'District',
                'LGA',
                'Shelf Location'
            ]);
            
            // Data rows
            $sn = 1;
            foreach ($files as $file) {
                fputcsv($out, [
                    $sn++,
                    $file->registry ?? '',
                    $file->batch_no ?? '',
                    $file->file_number ?? '',
                    $file->file_title ?? '',
                    $file->land_use_type ?? '',
                    $file->plot_number ?? '',
                    $file->lpkn_no ?? '',
                    $file->tp_no ?? '',
                    $file->district ?? '',
                    $file->lga ?? '',
                    $file->shelf_location ?? ''
                ]);
            }
            
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate Excel export (as CSV for now, can be upgraded to actual Excel later)
     */
    private function generateExcelExport($files, $filename)
    {
        // For now, generate CSV with Excel-friendly formatting
        // TODO: Implement actual Excel export using PhpSpreadsheet if needed
        return $this->generateCsvExport($files, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Soft delete an indexed file by setting is_deleted = 1
     */
    public function deleteIndexedFile($id)
    {
        try {
            $fileIndexing = FileIndexing::on('sqlsrv')->findOrFail($id);
            
            // Check if file exists and is not already deleted
            if ($fileIndexing->is_deleted == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'File is already deleted.'
                ], 400);
            }

            // Soft delete by setting is_deleted = 1
            $fileIndexing->is_deleted = 1;
            $fileIndexing->deleted_at = now();
            $fileIndexing->save();

            Log::info('File indexed record soft deleted', [
                'file_id' => $id,
                'file_number' => $fileIndexing->file_number,
                'file_title' => $fileIndexing->file_title,
                'deleted_by' => Auth::user()->email ?? 'system'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully.',
                'fileName' => $fileIndexing->file_title ?: $fileIndexing->file_number
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.'
            ], 404);
        } catch (Exception $e) {
            Log::error('Error deleting indexed file', [
                'file_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the file.'
            ], 500);
        }
    }

}