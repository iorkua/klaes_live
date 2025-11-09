<?php

namespace App\Http\Controllers;

use App\Models\FileIndexing;
use App\Models\PrintLabelBatch;
use App\Models\PrintLabelBatchItem;
use App\Models\RackShelfLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

class PrintLabelController extends Controller
{ 
    public function index(Request $request) {
        // Check if we should filter for ST records
        $showOnlyST = $request->get('url') === 'st';
        
        // Set page details based on filter type
        if ($showOnlyST) {
            $PageTitle = 'Print ST File Labels';
            $PageDescription = 'Generate and print labels for Sectional Titling files';
        } else {
            $PageTitle = 'Print File Labels';
            $PageDescription = 'Generate and print labels for physical files';
        }
        
        // Get statistics with error handling
        try {
            if ($showOnlyST) {
                // For ST files, don't require batch_no
                $availableFilesQuery = FileIndexing::on('sqlsrv')
                    ->where(function($query) {
                        $query->whereNotNull('main_application_id')
                              ->orWhereNotNull('subapplication_id');
                    })
                    ->whereDoesntHave('printLabelBatchItems');
            } else {
                // For regular files, require batch_no
                $availableFilesQuery = FileIndexing::on('sqlsrv')
                    ->whereNotNull('batch_no')
                    ->whereDoesntHave('printLabelBatchItems');
            }
            
            $availableFilesCount = $availableFilesQuery->count();
        } catch (\Exception $e) {
            // Fallback if tables don't exist yet or relationship issues
            Log::warning('Error counting available files for print labels', ['error' => $e->getMessage()]);
            $availableFilesCount = 0;
        }
        
        try {
            $recentBatches = PrintLabelBatch::with(['batchItems', 'creator'])
                ->recent(30)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            // Fallback if tables don't exist yet
            Log::warning('Error fetching recent batches', ['error' => $e->getMessage()]);
            $recentBatches = collect();
        }
        
        Log::info('Print Label page accessed', ['user_id' => auth()->id(), 'st_filter' => $showOnlyST]);
        return view('printlabel.index', compact(
            'PageTitle', 
            'PageDescription', 
            'availableFilesCount',
            'recentBatches',
            'showOnlyST'
        ));
    }

    /**
     * Return paginated list of files available for label printing
     */
    public function getAvailableFiles(Request $request)
    {
        try {
            // Log the start of the request for debugging
            Log::info('getAvailableFiles API called', [
                'user_id' => auth()->id(),
                'request_params' => $request->all()
            ]);

            $perPage = (int) $request->get('per_page', 30);
            $perPage = max(1, min($perPage, 100));

            $stFilter = $request->get('st_filter') === 'true';
            $search = trim((string) $request->get('search', ''));

            // Optimize: Check shelf_rack column existence only once with caching
            static $shelfRackCache = null;
            if ($shelfRackCache === null) {
                try {
                    $shelfRackCache = Schema::connection('sqlsrv')->hasColumn('grouping', 'shelf_rack');
                } catch (\Exception $e) {
                    Log::warning('Could not check shelf_rack column existence', ['error' => $e->getMessage()]);
                    $shelfRackCache = false;
                }
            }
            $groupingHasShelfRack = $shelfRackCache;

            // Build optimized select columns
            $selectColumns = [
                'file_indexings.id',
                'file_indexings.file_number', 
                'file_indexings.file_title',
                'file_indexings.plot_number',
                'file_indexings.district',
                'file_indexings.lga',
                'file_indexings.land_use_type',
                'file_indexings.shelf_location',
                'file_indexings.batch_no',
                'file_indexings.tracking_id as indexing_tracking_id',
                'file_indexings.sys_batch_no',
                'file_indexings.main_application_id',
                'file_indexings.subapplication_id',
                'file_indexings.st_fillno',
            ];

            // Only join expensive tables when search is provided or ST filter is active
            $needsGrouping = $search !== '' || $stFilter;
            $needsApplications = $stFilter;

            if ($needsGrouping) {
                $selectColumns = array_merge($selectColumns, [
                    'grouping.tracking_id as grouping_tracking_id',
                    'grouping.awaiting_fileno',
                    'grouping.mls_fileno',
                    'grouping.batch_no as grouping_batch_no',
                    'grouping.sys_batch_no as grouping_sys_batch_no',
                    'grouping.landuse',
                ]);

                if ($groupingHasShelfRack) {
                    $selectColumns[] = 'grouping.shelf_rack as grouping_shelf_rack';
                }
            }

            if ($needsApplications) {
                $selectColumns = array_merge($selectColumns, [
                    'mother_applications.fileno as mother_fileno',
                    'mother_applications.np_fileno as mother_np_fileno',
                    'mother_applications.application_type as mother_application_type',
                    'subapplications.fileno as sub_fileno',
                    'subapplications.np_fileno as sub_np_fileno',
                    'subapplications.application_type as sub_application_type',
                ]);
            }

            $query = FileIndexing::on('sqlsrv')
                ->select($selectColumns)
                ->where(function ($query) {
                    $query->whereNull('file_indexings.is_deleted')
                        ->orWhere('file_indexings.is_deleted', false);
                })
                ->whereDoesntHave('printLabelBatchItems');

            // Add joins only when needed
            if ($needsGrouping) {
                $query->leftJoin('grouping', 'grouping.awaiting_fileno', '=', 'file_indexings.file_number');
            }

            if ($needsApplications) {
                $query->leftJoin('mother_applications', 'file_indexings.main_application_id', '=', 'mother_applications.id')
                      ->leftJoin('subapplications', 'file_indexings.subapplication_id', '=', 'subapplications.id');
            }

            // Apply filters
            if (!$stFilter) {
                $query->whereNotNull('file_indexings.batch_no');
            } else {
                $query->where(function ($subQuery) {
                    $subQuery->whereNotNull('file_indexings.subapplication_id')
                        ->orWhereNotNull('file_indexings.main_application_id');
                });
            }

            // Optimize search: only search in joined columns if they're available
            if ($search !== '') {
                $query->where(function ($searchQuery) use ($search, $needsGrouping, $needsApplications) {
                    $like = '%' . $search . '%';
                    $searchQuery->where('file_indexings.file_number', 'like', $like)
                        ->orWhere('file_indexings.file_title', 'like', $like)
                        ->orWhere('file_indexings.plot_number', 'like', $like)
                        ->orWhere('file_indexings.district', 'like', $like)
                        ->orWhere('file_indexings.lga', 'like', $like);

                    if ($needsGrouping) {
                        $searchQuery->orWhere('grouping.awaiting_fileno', 'like', $like)
                            ->orWhere('grouping.mls_fileno', 'like', $like)
                            ->orWhere('grouping.tracking_id', 'like', $like);
                    }

                    if ($needsApplications) {
                        $searchQuery->orWhere('mother_applications.fileno', 'like', $like)
                            ->orWhere('mother_applications.np_fileno', 'like', $like)
                            ->orWhere('subapplications.fileno', 'like', $like)
                            ->orWhere('subapplications.np_fileno', 'like', $like);
                    }
                });
            }

            // Use limit instead of pagination for better performance on large datasets
            $files = $query
                ->orderByDesc('file_indexings.id')
                ->limit($perPage)
                ->get();

            $records = $files->map(function ($item) use ($groupingHasShelfRack, $needsGrouping, $needsApplications) {
                $record = [
                    'id' => $item->id,
                    'file_number' => $item->file_number,
                    'file_title' => $item->file_title,
                    'plot_number' => $item->plot_number,
                    'district' => $item->district,
                    'lga' => $item->lga,
                    'land_use_type' => $item->land_use_type,
                    'shelf_location' => $item->shelf_location,
                    'batch_no' => $item->batch_no,
                    'tracking_id' => $item->indexing_tracking_id,
                    'main_application_id' => $item->main_application_id,
                    'subapplication_id' => $item->subapplication_id,
                    'sys_batch_no' => $item->sys_batch_no,
                    'st_fillno' => $item->st_fillno,
                    // Default values for optional fields
                    'landuse' => null,
                    'awaiting_fileno' => null,
                    'indexing_mls_fileno' => null,
                    'mother_fileno' => null,
                    'mother_np_fileno' => null,
                    'sub_fileno' => null,
                    'sub_np_fileno' => null,
                    'grouping_sys_batch_no' => null,
                    'grouping_shelf_rack' => null,
                ];

                // Add grouping data if available
                if ($needsGrouping) {
                    $record['landuse'] = $item->landuse ?? null;
                    $record['tracking_id'] = $item->grouping_tracking_id ?? $item->indexing_tracking_id;
                    $record['awaiting_fileno'] = $item->awaiting_fileno ?? null;
                    $record['indexing_mls_fileno'] = $item->mls_fileno ?? null;
                    $record['batch_no'] = $item->batch_no ?? $item->grouping_batch_no;
                    $record['grouping_sys_batch_no'] = $item->grouping_sys_batch_no ?? null;
                    $record['land_use_type'] = $item->land_use_type ?? $item->landuse;

                    if ($groupingHasShelfRack) {
                        $record['grouping_shelf_rack'] = $item->grouping_shelf_rack ?? null;
                    }
                }

                // Add application data if available  
                if ($needsApplications) {
                    $record['mother_fileno'] = $item->mother_fileno ?? null;
                    $record['mother_np_fileno'] = $item->mother_np_fileno ?? null;
                    $record['sub_fileno'] = $item->sub_fileno ?? null;
                    $record['sub_np_fileno'] = $item->sub_np_fileno ?? null;
                }

                return $record;
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => $records,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'has_more_pages' => $files->count() >= $perPage,
                    'next_page_url' => $files->count() >= $perPage ? url()->current() . '?page=2' : null,
                    'prev_page_url' => null,
                    'last_page' => 1,
                    'total' => null,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error fetching available files for printing', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch available files.',
            ], 500);
        }
    }

    /**
     * Preview grouping table records for batch mode
     */
    public function previewGroupingBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sys_batch_no' => 'required|string|max:255',
            'start' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $sysBatchNo = $validated['sys_batch_no'];
        $start = isset($validated['start']) ? (int) $validated['start'] : 1;
        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 30;
        $limit = max(1, min($limit, 100));
        $end = $start + $limit - 1;
        $groupingHasShelfRack = Schema::connection('sqlsrv')->hasColumn('grouping', 'shelf_rack');

        $columnList = $this->buildGroupingColumnList($groupingHasShelfRack);
        $outerSelect = implode(', ', $columnList);
        $innerSelect = implode(",\n            ", $columnList);

        try {
            $sql = <<<SQL
SELECT {$outerSelect}
FROM (
    SELECT 
        {$innerSelect},
        ROW_NUMBER() OVER (ORDER BY id ASC) AS row_num
    FROM grouping WITH (NOLOCK)
    WHERE sys_batch_no = ?
) AS ranked
WHERE ranked.row_num BETWEEN ? AND ?
ORDER BY ranked.row_num ASC
SQL;

            try {
                $records = collect(DB::connection('sqlsrv')->select($sql, [$sysBatchNo, $start, $end]));
            } catch (QueryException $queryException) {
                if ($groupingHasShelfRack && Str::contains($queryException->getMessage(), "shelf_rack")) {
                    Log::warning('Retrying grouping preview without shelf_rack column', [
                        'batch_no' => $sysBatchNo,
                    ]);

                    $groupingHasShelfRack = false;
                    $columnList = $this->buildGroupingColumnList(false);
                    $outerSelect = implode(', ', $columnList);
                    $innerSelect = implode(",\n            ", $columnList);

                    $sql = <<<SQL
SELECT {$outerSelect}
FROM (
    SELECT 
        {$innerSelect},
        ROW_NUMBER() OVER (ORDER BY id ASC) AS row_num
    FROM grouping WITH (NOLOCK)
    WHERE sys_batch_no = ?
) AS ranked
WHERE ranked.row_num BETWEEN ? AND ?
ORDER BY ranked.row_num ASC
SQL;

                    $records = collect(DB::connection('sqlsrv')->select($sql, [$sysBatchNo, $start, $end]));
                } else {
                    throw $queryException;
                }
            }

            $formatted = $records->map(function ($row) use ($groupingHasShelfRack) {
                $primary = $row->mls_fileno
                    ?? $row->awaiting_fileno
                    ?? $row->tracking_id;

                $displayTitle = $row->tracking_id
                    ?? $row->awaiting_fileno
                    ?? 'Pending tracking ID';

                return [
                    'id' => (int) $row->id,
                    'file_number' => $primary ?? '—',
                    'file_title' => $displayTitle,
                    'plot_number' => null,
                    'district' => null,
                    'lga' => null,
                    'batch_no' => $row->sys_batch_no,
                    'land_use_type' => $row->landuse,
                    'tracking_id' => $row->tracking_id,
                    'awaiting_fileno' => $row->awaiting_fileno,
                    'indexing_mls_fileno' => $row->mls_fileno,
                    'shelf_label' => $groupingHasShelfRack ? ($row->shelf_rack ?? null) : null,
                    'shelf_location' => $groupingHasShelfRack ? ($row->shelf_rack ?? null) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'records' => $formatted,
                    'requested' => [
                        'sys_batch_no' => $sysBatchNo,
                        'start' => $start,
                        'limit' => $limit,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to preview grouping batch records', [
                'batch_no' => $sysBatchNo,
                'start' => $start,
                'limit' => $limit,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to preview grouping batch records.',
            ], 500);
        }
    }

    /**
     * Create a new label batch
     */
    public function createBatch(Request $request)
    {
        $source = $request->input('source', 'file_indexings');
        $source = $source === 'grouping' ? 'grouping' : 'file_indexings';

        $baseRules = [
            'label_format' => 'required|in:standard,compact,qr_code,30-in-1',
            'orientation' => 'required|in:portrait,landscape',
            'batch_size' => 'nullable|integer|min:1|max:100',
        ];

        if ($source === 'grouping') {
            $rules = array_merge($baseRules, [
                'sys_batch_no' => 'required|string|max:255',
                'records' => 'required|array|min:1|max:100',
                'records.*.id' => 'required|integer',
                'records.*.file_number' => 'nullable|string|max:255',
                'records.*.file_title' => 'nullable|string|max:255',
                'records.*.tracking_id' => 'nullable|string|max:255',
                'records.*.awaiting_fileno' => 'nullable|string|max:255',
                'records.*.indexing_mls_fileno' => 'nullable|string|max:255',
                'records.*.land_use_type' => 'nullable|string|max:255',
                'records.*.plot_number' => 'nullable|string|max:255',
                'records.*.district' => 'nullable|string|max:255',
                'records.*.lga' => 'nullable|string|max:255',
                'records.*.shelf_label' => 'nullable|string|max:255',
            ]);
        } else {
            $rules = array_merge($baseRules, [
                'file_ids' => 'required|array|min:1|max:30',
                'file_ids.*' => 'integer',
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $batchSize = $validated['batch_size'] ?? ($source === 'grouping'
            ? count($validated['records'])
            : count($validated['file_ids']));

        try {
            $result = DB::connection('sqlsrv')->transaction(function () use ($source, $validated, $batchSize) {
                $batch = PrintLabelBatch::create([
                    'batch_number' => PrintLabelBatch::generateBatchNumber(),
                    'batch_size' => $batchSize,
                    'label_format' => $validated['label_format'],
                    'orientation' => $validated['orientation'],
                    'status' => PrintLabelBatch::STATUS_PENDING,
                    'created_by' => auth()->id(),
                ]);

                $itemsCreated = 0;
                $context = [];

                if ($source === 'grouping') {
                    $records = collect($validated['records']);
                    $now = now();
                    $resolvedItems = [];
                    $missingReferences = [];
                    $duplicateReferences = [];
                    $indexedIds = [];

                    foreach ($records as $record) {
                        $indexing = $this->resolveFileIndexingForGroupingRecord($record);

                        if (!$indexing) {
                            $missingReferences[] = $record['tracking_id']
                                ?? $record['awaiting_fileno']
                                ?? $record['file_number']
                                ?? $record['indexing_mls_fileno']
                                ?? 'unknown';
                            continue;
                        }

                        if (in_array($indexing->id, $indexedIds, true)) {
                            $duplicateReferences[] = $indexing->file_number
                                ?? $indexing->tracking_id
                                ?? (string) $indexing->id;
                            continue;
                        }

                        $indexedIds[] = $indexing->id;

                        $fileNumber = $record['file_number']
                            ?? $record['indexing_mls_fileno']
                            ?? $record['awaiting_fileno']
                            ?? $indexing->file_number
                            ?? $indexing->tracking_id;

                        $title = $record['file_title']
                            ?? $record['tracking_id']
                            ?? $record['awaiting_fileno']
                            ?? $fileNumber
                            ?? 'Grouping record';

                        $shelfLocation = $record['shelf_label']
                            ?? $indexing->shelf_location
                            ?? null;

                        $resolvedItems[] = [
                            'batch_id' => $batch->id,
                            'file_indexing_id' => $indexing->id,
                            'file_number' => $fileNumber,
                            'file_title' => $title,
                            'plot_number' => $record['plot_number'] ?? null,
                            'district' => $record['district'] ?? $indexing->district,
                            'lga' => $record['lga'] ?? $indexing->lga,
                            'land_use_type' => $record['land_use_type'] ?? $indexing->land_use_type,
                            'shelf_location' => $shelfLocation,
                            'label_position' => count($resolvedItems) + 1,
                            'qr_code_data' => json_encode([
                                'file_number' => $fileNumber,
                                'tracking_id' => $record['tracking_id'] ?? $indexing->tracking_id,
                                'awaiting_fileno' => $record['awaiting_fileno'] ?? null,
                                'indexing_mls_fileno' => $record['indexing_mls_fileno'] ?? null,
                                'land_use_type' => $record['land_use_type'] ?? $indexing->land_use_type,
                                'generated_at' => $now->toIso8601String(),
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ];
                    }

                    if (empty($resolvedItems)) {
                        throw new \RuntimeException('No eligible indexed files were found for the selected grouping records.');
                    }

                    if (!empty($missingReferences)) {
                        Log::warning('Missing indexed files while creating label batch from grouping records', [
                            'batch_id' => $batch->id,
                            'missing' => $missingReferences,
                        ]);
                    }

                    foreach ($resolvedItems as $item) {
                        PrintLabelBatchItem::create($item);
                    }

                    if (!empty($duplicateReferences)) {
                        Log::warning('Skipped duplicate grouping references while creating label batch', [
                            'batch_id' => $batch->id,
                            'duplicates' => $duplicateReferences,
                        ]);
                    }

                    $warnings = [];

                    if (!empty($missingReferences)) {
                        $warnings['missing_references'] = $missingReferences;
                    }

                    if (!empty($duplicateReferences)) {
                        $warnings['duplicate_references'] = $duplicateReferences;
                    }

                    $itemsCreated = count($resolvedItems);
                    $batch->update([
                        'status' => PrintLabelBatch::STATUS_GENERATED,
                        'generated_count' => $itemsCreated,
                        'updated_by' => auth()->id(),
                    ]);

                    // Assign rack/shelf labels to the batch
                    $this->assignRackShelfLabelsToMBatch($batch->id, $validated['sys_batch_no']);

                    $context = [
                        'source' => 'grouping',
                        'sys_batch_no' => $validated['sys_batch_no'],
                    ];

                    if (!empty($warnings)) {
                        $context['warnings'] = $warnings;
                    }
                } else {
                    $files = FileIndexing::on('sqlsrv')->whereIn('id', $validated['file_ids'])->get();

                    if ($files->count() !== count($validated['file_ids'])) {
                        throw new \RuntimeException('Some selected files were not found.');
                    }

                    foreach ($files as $index => $file) {
                        PrintLabelBatchItem::create([
                            'batch_id' => $batch->id,
                            'file_indexing_id' => $file->id,
                            'file_number' => $file->file_number,
                            'file_title' => $file->file_title,
                            'plot_number' => $file->plot_number,
                            'district' => $file->district,
                            'lga' => $file->lga,
                            'land_use_type' => $file->land_use_type,
                            'shelf_location' => $file->shelf_location,
                            'label_position' => $index + 1,
                        ]);
                    }

                    $itemsCreated = $files->count();
                    $batch->update([
                        'status' => PrintLabelBatch::STATUS_GENERATED,
                        'generated_count' => $itemsCreated,
                        'updated_by' => auth()->id(),
                    ]);

                    $context = [
                        'source' => 'file_indexings',
                    ];
                }

                return [
                    'batch' => $batch->fresh(['batchItems']),
                    'items_created' => $itemsCreated,
                    'context' => $context,
                ];
            });

            Log::info('Label batch created successfully', [
                'batch_id' => $result['batch']->id,
                'batch_number' => $result['batch']->batch_number,
                'items_created' => $result['items_created'],
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Label batch created successfully',
                'data' => [
                    'batch_id' => $result['batch']->id,
                    'batch_number' => $result['batch']->batch_number,
                    'file_count' => $result['items_created'],
                    'source' => $result['context']['source'] ?? null,
                    'sys_batch_no' => $result['context']['sys_batch_no'] ?? null,
                    'warnings' => $result['context']['warnings'] ?? [],
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error creating label batch', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creating batch: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign rack/shelf labels to a batch
     */
    private function assignRackShelfLabelsToMBatch($batchId, $sysBatchNo)
    {
        try {
            // Get the next available rack/shelf label
            $rackShelfLabel = RackShelfLabel::getNextAvailableLabel();
            
            if ($rackShelfLabel) {
                // Assign the label to this batch using the batch ID
                $rackShelfLabel->assignToBatch($batchId);
                
                Log::info('Assigned rack/shelf label to batch', [
                    'batch_id' => $batchId,
                    'sys_batch_no' => $sysBatchNo,
                    'rack_shelf_label_id' => $rackShelfLabel->id,
                    'full_label' => $rackShelfLabel->full_label,
                ]);
                
                return $rackShelfLabel;
            } else {
                Log::warning('No available rack/shelf labels for batch assignment', [
                    'batch_id' => $batchId,
                    'sys_batch_no' => $sysBatchNo,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error assigning rack/shelf labels to batch', [
                'batch_id' => $batchId,
                'sys_batch_no' => $sysBatchNo,
                'error' => $e->getMessage(),
            ]);
        }
        
        return null;
    }

    /**
     * Get generated batches (alias for compatibility)
     */
    public function getBatches(Request $request)
    {
        return $this->getGeneratedBatches($request);
    }

    /**
     * Get generated batches
     */
    public function getGeneratedBatches(Request $request)
    {
        try {
            $query = PrintLabelBatch::with(['batchItems', 'creator'])
                ->where('status', '!=', PrintLabelBatch::STATUS_PENDING);

            // Apply filters
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Apply pagination
            $perPage = $request->get('per_page', 20);
            $batches = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $batches->items(),
                'pagination' => [
                    'current_page' => $batches->currentPage(),
                    'last_page' => $batches->lastPage(),
                    'per_page' => $batches->perPage(),
                    'total' => $batches->total(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching generated batches', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching batches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch details with items
     */
    public function getBatchDetails($batchId)
    {
        try {
            $batch = PrintLabelBatch::with(['batchItems.fileIndexing', 'creator'])
                ->findOrFail($batchId);

            return response()->json([
                'success' => true,
                'data' => $batch
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching batch details', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching batch details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch for printing (with file details)
     */
    public function getBatchForPrinting($batchId)
    {
        try {
            $batch = PrintLabelBatch::with(['batchItems'])
                ->findOrFail($batchId);

            $fileIndexingIds = $batch->batchItems
                ->pluck('file_indexing_id')
                ->filter()
                ->unique()
                ->values();

            $indexingDetails = collect();

            if ($fileIndexingIds->isNotEmpty()) {
                $indexingDetails = FileIndexing::on('sqlsrv')
                    ->select([
                        'file_indexings.*',
                        'mother_applications.fileno as mother_fileno',
                        'mother_applications.np_fileno as mother_np_fileno',
                        'subapplications.fileno as sub_fileno',
                        'subapplications.np_fileno as sub_np_fileno'
                    ])
                    ->leftJoin('mother_applications', 'file_indexings.main_application_id', '=', 'mother_applications.id')
                    ->leftJoin('subapplications', 'file_indexings.subapplication_id', '=', 'subapplications.id')
                    ->whereIn('file_indexings.id', $fileIndexingIds)
                    ->get()
                    ->keyBy('id');
            }

            // Get the rack/shelf label assigned to this batch
            $rackShelfLabel = RackShelfLabel::getLabelsForBatch($batchId)->first();

            // Transform batch items to match the expected format for printing
            $files = $batch->batchItems->map(function ($item) use ($indexingDetails, $rackShelfLabel) {
                $details = $indexingDetails->get($item->file_indexing_id);
                
                // Determine the best shelf/rack value to display
                $shelfValue = null;
                if ($rackShelfLabel) {
                    $shelfValue = $rackShelfLabel->full_label;
                } else {
                    $shelfValue = $item->shelf_location ?? optional($details)->shelf_location;
                }

                return [
                    'id' => $item->file_indexing_id,
                    'file_number' => $item->file_number,
                    'file_title' => $item->file_title,
                    'plot_number' => $item->plot_number,
                    'district' => $item->district,
                    'lga' => $item->lga,
                    'land_use_type' => $item->land_use_type,
                    'shelf_location' => $item->shelf_location ?? optional($details)->shelf_location,
                    'shelf_value' => $shelfValue, // This is what the frontend uses
                    'shelf_label' => $shelfValue, // Alternative field name for compatibility
                    'batch_no' => $item->file_number, // Use file_number as batch reference
                    'main_application_id' => optional($details)->main_application_id,
                    'subapplication_id' => optional($details)->subapplication_id,
                    'mother_fileno' => optional($details)->mother_fileno,
                    'mother_np_fileno' => optional($details)->mother_np_fileno,
                    'sub_fileno' => optional($details)->sub_fileno,
                    'sub_np_fileno' => optional($details)->sub_np_fileno,
                    'st_fillno' => optional($details)->st_fillno,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'batch' => $batch,
                    'files' => $files
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching batch for printing', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching batch for printing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark batch as printed
     */
    public function markBatchAsPrinted($batchId)
    {
        try {
            $batch = PrintLabelBatch::findOrFail($batchId);
            $batch->markAsPrinted();

            // Mark all items as printed
            $batch->batchItems()->update([
                'is_printed' => true,
                'printed_at' => now()
            ]);

            Log::info('Batch marked as printed', [
                'batch_id' => $batchId,
                'batch_number' => $batch->batch_number,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Batch marked as printed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error marking batch as printed', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error marking batch as printed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a batch
     */
    public function deleteBatch($batchId)
    {
        try {
            $batch = PrintLabelBatch::findOrFail($batchId);
            
            // Only allow deletion of pending or generated batches
            if (in_array($batch->status, [PrintLabelBatch::STATUS_PRINTED, PrintLabelBatch::STATUS_COMPLETED])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete printed or completed batches'
                ], 400);
            }

            $batchNumber = $batch->batch_number;
            $batch->delete();

            Log::info('Batch deleted', [
                'batch_id' => $batchId,
                'batch_number' => $batchNumber,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Batch deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting batch', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error deleting batch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get print statistics
     */
    public function getStatistics()
    {
        try {
            // Check if ST filter is requested
            $stFilter = request('st_filter') === 'true';
            
            // Base query for available files
            $availableFilesQuery = FileIndexing::on('sqlsrv')->whereNotNull('batch_no')
                ->whereDoesntHave('printLabelBatchItems');
            
            // Apply ST filter if requested
            if ($stFilter) {
                $availableFilesQuery->where(function($query) {
                    $query->whereNotNull('subapplication_id')
                          ->orWhereHas('motherApplication', function($subQuery) {
                              $subQuery->where('application_type', 'like', '%sectional%')
                                       ->orWhere('application_type', 'like', '%ST%');
                          });
                });
            }
            
            $stats = [
                'available_files' => $availableFilesQuery->count(),
                'total_batches' => PrintLabelBatch::count(),
                'pending_batches' => PrintLabelBatch::where('status', PrintLabelBatch::STATUS_PENDING)->count(),
                'generated_batches' => PrintLabelBatch::where('status', PrintLabelBatch::STATUS_GENERATED)->count(),
                'printed_batches' => PrintLabelBatch::where('status', PrintLabelBatch::STATUS_PRINTED)->count(),
                'completed_batches' => PrintLabelBatch::where('status', PrintLabelBatch::STATUS_COMPLETED)->count(),
                'total_labels_printed' => PrintLabelBatchItem::where('is_printed', true)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Attempt to match a grouping record to an indexed file
     */
    protected function resolveFileIndexingForGroupingRecord(array $record): ?FileIndexing
    {
        $query = FileIndexing::on('sqlsrv')
            ->select('file_indexings.*')
            ->leftJoin('grouping', 'grouping.awaiting_fileno', '=', 'file_indexings.file_number')
            ->where(function ($builder) {
                $builder->whereNull('file_indexings.is_deleted')
                    ->orWhere('file_indexings.is_deleted', false)
                    ->orWhere('file_indexings.is_deleted', 0);
            });

        $hasCriteria = false;

        $query->where(function ($builder) use ($record, &$hasCriteria) {
            if (!empty($record['file_indexing_id'])) {
                $builder->orWhere('file_indexings.id', (int) $record['file_indexing_id']);
                $hasCriteria = true;
            }

            if (!empty($record['indexing_id'])) {
                $builder->orWhere('file_indexings.id', (int) $record['indexing_id']);
                $hasCriteria = true;
            }

            if (!empty($record['id'])) {
                $builder->orWhere('grouping.id', (int) $record['id']);
                $hasCriteria = true;
            }

            if (!empty($record['file_number'])) {
                $builder->orWhere('file_indexings.file_number', $record['file_number']);
                $hasCriteria = true;
            }

            if (!empty($record['indexing_mls_fileno'])) {
                $builder->orWhere('file_indexings.file_number', $record['indexing_mls_fileno']);
                $hasCriteria = true;
            }

            if (!empty($record['awaiting_fileno'])) {
                $builder->orWhere('file_indexings.file_number', $record['awaiting_fileno']);
                $hasCriteria = true;
            }

            if (!empty($record['tracking_id'])) {
                $builder->orWhere('file_indexings.tracking_id', $record['tracking_id']);
                $hasCriteria = true;
            }

            if (!empty($record['sys_batch_no'])) {
                $builder->orWhere('file_indexings.sys_batch_no', $record['sys_batch_no']);
                $hasCriteria = true;
            }

            if (!empty($record['st_fillno'])) {
                $builder->orWhere('file_indexings.st_fillno', $record['st_fillno']);
                $hasCriteria = true;
            }
        });

        if (!$hasCriteria) {
            return null;
        }

        return $query
            ->orderByDesc('file_indexings.updated_at')
            ->orderByDesc('file_indexings.id')
            ->first();
    }

    protected function buildGroupingColumnList(bool $includeShelfRack): array
    {
        $columns = [
            'id',
            'sys_batch_no',
            'tracking_id',
            'awaiting_fileno',
            'mls_fileno',
            'landuse',
        ];

        if ($includeShelfRack) {
            $columns[] = 'shelf_rack';
        }

        return $columns;
    }
}
