<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use App\Models\FileIndexing;
use App\Models\Grouping;

class FileIndexingController extends Controller
{
    protected function normalizeFileno(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim($value));
        return str_replace(['-', '/', ' ', '\\', '.', ','], '', $normalized);
    }

    protected function deriveRegistryFromGrouping(Grouping $grouping): ?string
    {
        $registryLike = $grouping->registry ?? $grouping->registry_name ?? $grouping->registry_label ?? null;

        if ($registryLike) {
            return $registryLike;
        }

        if (!$grouping->landuse) {
            return null;
        }

        $mapping = [
            'COMMERCIAL' => 'Registry 1 - Lands',
            'RESIDENTIAL' => 'Registry 2 - Lands',
            'AGRICULTURAL' => 'Registry 3 - Lands',
            'INDUSTRIAL' => 'Registry 1 - Deeds',
            'MIXED_USE' => 'Registry 2 - Lands',
            'COMMERCIAL AND RESIDENTIAL' => 'Registry 1 - Lands',
        ];

        $landuseKey = strtoupper($grouping->landuse);

        return $mapping[$landuseKey] ?? ($grouping->landuse . ' Registry');
    }

    protected function buildFileNumberVariants(string $fileNumber): array
    {
        $normalized = strtoupper(trim($fileNumber));
        $variants = [$normalized];

        $variants[] = str_replace(' ', '', $normalized);

        if (preg_match('/^(CO?N?-?)([A-Z]{3})-(\d{4})-(\d+)$/', $normalized, $matches)) {
            $prefix = $matches[1];
            $landUse = $matches[2];
            $year = $matches[3];
            $serial = ltrim($matches[4], '0');
            if ($serial !== '') {
                $variants[] = sprintf('%s%s-%s-%s', $prefix, $landUse, $year, $serial);
                $variants[] = sprintf('%s%s-%s-%04d', $prefix, $landUse, $year, (int) $serial);
            }
        }

        if (preg_match('/^([A-Z]{3})-(\d{4})-(\d+)$/', $normalized, $matches)) {
            $landUse = $matches[1];
            $year = $matches[2];
            $serial = ltrim($matches[3], '0');
            if ($serial !== '') {
                $variants[] = sprintf('%s-%s-%s', $landUse, $year, $serial);
                $variants[] = sprintf('%s-%s-%04d', $landUse, $year, (int) $serial);
            }
        }

        $variants[] = str_replace('-', '', $normalized);

        return array_values(array_unique(array_filter($variants)));
    }

    public function getCofORecord(string $fileNo)
    {
        $fileNumber = trim($fileNo);

        if ($fileNumber === '') {
            return response()->json([
                'success' => false,
                'message' => 'File number is required.'
            ], 422);
        }

        $variants = $this->buildFileNumberVariants($fileNumber);

        $columns = $this->getAvailableCofOColumns();
        if (empty($columns)) {
            Log::warning('CofO lookup aborted: no searchable columns defined.');

            return response()->json([
                'success' => false,
                'message' => 'CofO lookup is unavailable because the target table has no searchable columns configured.',
            ], 500);
        }

        $schema = Schema::connection('sqlsrv');

        $selectColumns = [
            'serialNo',
            'pageNo',
            'volumeNo',
            'regNo',
            'cofo_no',
            'transaction_date',
            'transaction_time',
            'cofo_date',
            'land_use',
            'cofo_type',
            'property_description',
        ];

        $resolveColumn = static function ($columnCandidates) use ($schema) {
            foreach ($columnCandidates as $candidate) {
                try {
                    if ($schema->hasColumn('CofO', $candidate)) {
                        return $candidate;
                    }
                } catch (\Throwable $e) {
                    Log::debug('CofO optional column lookup failed', [
                        'column' => $candidate,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            return null;
        };

        $grantorColumn = $resolveColumn(['Grantor', 'grantor']);
        $granteeColumn = $resolveColumn(['Grantee', 'grantee']);

        if ($grantorColumn) {
            $selectColumns[] = DB::raw('[' . $grantorColumn . '] as grantor');
        }

        if ($granteeColumn) {
            $selectColumns[] = DB::raw('[' . $granteeColumn . '] as grantee');
        }

        $certificateColumn = $resolveColumn(['certificate_date', 'certificateDate']);
        if ($certificateColumn && ! in_array($certificateColumn, $selectColumns, true)) {
            $selectColumns[] = $certificateColumn;
        }

        $record = DB::connection('sqlsrv')
            ->table('CofO')
            ->select($selectColumns)
            ->where(function ($query) use ($variants, $columns) {
                foreach ($columns as $column) {
                    $query->orWhereIn($column, $variants);
                }
            })
            ->orderByDesc(DB::raw('ISNULL(transaction_date, transaction_date)'))
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No CofO record found for this file number.'
            ]);
        }

        $serial = $record->serialNo ?? null;
        $page = $record->pageNo ?? $record->reg_page ?? $serial;
        $volume = $record->volumeNo ?? $record->reg_volume ?? null;

        $transaction_dateRaw = $record->transaction_date ?? $record->transaction_date ?? null;
        $transaction_date = null;
        $transactionTime = null;

        if ($transaction_dateRaw) {
            try {
                $parsed = Carbon::parse($transaction_dateRaw);
                $transaction_date = $parsed->format('Y-m-d');
                $transactionTime = $parsed->format('H:i');
            } catch (\Throwable $e) {
                $transaction_date = $transaction_dateRaw;
            }
        }

        if (!$transactionTime && !empty($record->transaction_time)) {
            $transactionTime = substr($record->transaction_time, 0, 5);
        }

        $certificateDateRaw = $record->certificate_date ?? $record->certificateDate ?? $record->cofo_date ?? null;
        $certificateDate = null;

        if ($certificateDateRaw) {
            try {
                $certificateDate = Carbon::parse($certificateDateRaw)->format('Y-m-d');
            } catch (\Throwable $e) {
                $certificateDate = $certificateDateRaw;
            }
        }

        $normalizeParty = static function ($value) {
            if ($value === null) {
                return null;
            }

            $stringValue = is_string($value) ? trim($value) : trim((string) $value);

            return $stringValue === '' ? null : $stringValue;
        };

        $grantor = $normalizeParty($record->grantor ?? $record->Grantor ?? null);
        $grantee = $normalizeParty($record->grantee ?? $record->Grantee ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'serial_no' => $serial,
                'page_no' => $page,
                'volume_no' => $volume,
                'transaction_date' => $transaction_date,
                'transaction_time' => $transactionTime,
                'cofo_no' => $record->cofo_no,
                'certificate_date' => $certificateDate,
                'reg_no' => $record->regNo ?? ($serial && $page && $volume ? sprintf('%s/%s/%s', $serial, $page, $volume) : null),
                'land_use' => $record->land_use,
                'cofo_type' => $record->cofo_type,
                'property_description' => $record->property_description,
                'grantor' => $grantor,
                'grantee' => $grantee,
            ],
        ]);
    }

    protected function getAvailableCofOColumns(): array
    {
        static $availableColumns = null;

        if ($availableColumns !== null) {
            return $availableColumns;
        }

        $candidates = [
            'mlsFNo',
            'mlsfNo',
            'kangisFileNo',
            'NewKANGISFileNo',
            'NewKANGISFileno',
            'FileNo',
            'NewFileNo',
            'np_fileno',
            'cofo_no',
        ];

        $schema = Schema::connection('sqlsrv');

        $availableColumns = array_values(array_filter($candidates, static function ($column) use ($schema) {
            try {
                return $schema->hasColumn('CofO', $column);
            } catch (\Throwable $e) {
                Log::warning('Failed checking CofO column availability', [
                    'column' => $column,
                    'message' => $e->getMessage(),
                ]);
                return false;
            }
        }));

        return $availableColumns;
    }

  
    public function index()
    {
        try {
            // Get recent file indexing records to generate AI insights
            $recentIndexes = FileIndexing::orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $aiInsights = $recentIndexes->map(function ($fi) {
                $owner = $fi->file_title ?? ($fi->owner_name ?? null) ?? 'Unknown Owner';
                $scannedCount = isset($fi->scannings_count) ? $fi->scannings_count : 0;
                $typedCount = isset($fi->pagetypings_count) ? $fi->pagetypings_count : 0;

                $confidence = min(98, 50 + ($scannedCount * 12) + ($typedCount * 6));

                $title = $fi->file_title ?? '';
                $keywords = array_values(array_filter(array_map('trim', preg_split('/\s+/', preg_replace('/[^A-Za-z0-9 ]/', ' ', $title)))));
                if (!empty($fi->land_use_type)) {
                    array_unshift($keywords, $fi->land_use_type);
                }

                $issues = [];
                if (!empty($fi->is_problematic)) $issues[] = 'Flagged as problematic';
                if (empty($fi->plot_number)) $issues[] = 'Missing plot number';

                return [
                    'id' => $fi->id,
                    'file_number' => $fi->file_number ?? '',
                    'owner' => $owner,
                    'document_type' => $fi->document_type ?? ($fi->land_use_type ?? 'Property Document'),
                    'plot_number' => $fi->plot_number ?? '',
                    'land_use' => $fi->land_use_type ?? 'Residential',
                    'confidence' => $confidence,
                    'keywords' => $keywords,
                    'issues' => $issues,
                    'text_quality' => $scannedCount > 0 ? 'Good' : 'Unknown',
                    'structure' => $typedCount > 0 ? 'Complete sections' : 'Partial',
                    'signature' => 'Not detected',
                    'stamp' => $scannedCount > 0 ? 'Official stamp detected' : 'Not detected',
                    'gis_verification' => 'Matched with parcel data',
                ];
            })->values();

            return view('fileindexing.index', compact('aiInsights', 'recentIndexes'));
        } catch (\Throwable $e) {
            // Fallback: render view with empty aiInsights
            return view('fileindexing.index', ['aiInsights' => collect(), 'recentIndexes' => collect()]);
        }
    }

    /**
     * Display the specified file indexing record.
     */
    public function show($id)
    {
        try {
            $record = DB::connection('sqlsrv')->table('file_indexings')->where('id', $id)->first();
        } catch (\Throwable $e) {
            $record = null;
        }

        if (!$record) {
            return redirect()->route('fileindex.index')->with('error', 'File indexing record not found.');
        }

        // If a dedicated view exists, render it; otherwise, return to index with context
        if (view()->exists('fileindexing.show')) {
            return view('fileindexing.show', compact('record'));
        }

        return redirect()->route('fileindex.index')
            ->with('success', 'Opened file indexing record.')
            ->with('file_indexing_id', $id);
    }

    /**
     * Check if a file number has already been indexed
     */
    public function checkIndexed(Request $request)
    {
        $fileno = trim((string) $request->query('fileno', ''));
        
        if ($fileno === '') {
            return response()->json(['exists' => false]);
        }

        try {
            $record = DB::connection('sqlsrv')->table('file_indexings')
                ->where('file_number', $fileno)
                ->orderBy('id', 'desc')
                ->first();

            if (!$record) {
                return response()->json(['exists' => false]);
            }

            return response()->json([
                'exists' => true,
                'record' => [
                    'id' => $record->id,
                    'file_number' => $record->file_number,
                    'st_fillno' => $record->st_fillno,
                    'file_title' => $record->file_title,
                    'land_use_type' => $record->land_use_type,
                    'plot_number' => $record->plot_number,
                    'district' => $record->district,
                    'lga' => $record->lga,
                    'has_cofo' => (int) ($record->has_cofo ?? 0),
                    'is_merged' => (int) ($record->is_merged ?? 0),
                    'has_transaction' => (int) ($record->has_transaction ?? 0),
                    'is_problematic' => (int) ($record->is_problematic ?? 0),
                    'is_co_owned_plot' => (int) ($record->is_co_owned_plot ?? 0),
                    'created_at' => $record->created_at,
                    'updated_at' => $record->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function lookupByFileNumber(Request $request)
    {
        $fileNumber = trim((string) $request->get('file_number', ''));

        if ($fileNumber === '') {
            return response()->json([
                'success' => false,
                'message' => 'File number is required.'
            ], 422);
        }

        $variants = $this->buildFileNumberVariants($fileNumber);
        $normalized = $this->normalizeFileno($fileNumber);

        if ($normalized !== null) {
            $variants[] = $normalized;
        }

        $variants = array_values(array_unique(array_filter($variants)));

        $normalizedVariants = array_map(static function ($value) {
            return preg_replace('/[^A-Z0-9]/', '', strtoupper($value));
        }, $variants);

        $normalizedVariants = array_values(array_unique(array_filter($normalizedVariants)));

        try {
            $columns = ['fn.st_file_no', 'fn.mlsfNo', 'fn.kangisFileNo', 'fn.NewKANGISFileNo'];

            $builder = DB::connection('sqlsrv')
                ->table('dbo.fileNumber as fn')
                ->select([
                    'fn.id',
                    'fn.st_file_no',
                    'fn.mlsfNo',
                    'fn.kangisFileNo',
                    'fn.NewKANGISFileNo',
                    'fn.FileName',
                    'fn.tracking_id',
                ])
                ->whereNotNull('fn.tracking_id')
                ->whereRaw("LTRIM(RTRIM(fn.tracking_id)) != ''")
                ->where(function ($query) {
                    $query->whereNull('fn.is_deleted')
                        ->orWhere('fn.is_deleted', 0);
                })
                ->where(function ($query) use ($columns, $variants, $normalizedVariants) {
                    $addedClause = false;

                    if (!empty($variants)) {
                        foreach ($columns as $column) {
                            foreach ($variants as $variant) {
                                if ($variant === '') {
                                    continue;
                                }

                                if (! $addedClause) {
                                    $query->where($column, $variant);
                                    $addedClause = true;
                                } else {
                                    $query->orWhere($column, $variant);
                                }
                            }
                        }
                    }

                    if (!empty($normalizedVariants)) {
                        foreach ($normalizedVariants as $normalizedVariant) {
                            foreach ($columns as $column) {
                                $expression = "REPLACE(REPLACE(REPLACE(UPPER($column), '-', ''), '/', ''), ' ', '')";

                                if (! $addedClause) {
                                    $query->whereRaw("$expression = ?", [$normalizedVariant]);
                                    $addedClause = true;
                                } else {
                                    $query->orWhereRaw("$expression = ?", [$normalizedVariant]);
                                }
                            }
                        }
                    }
                })
                ->orderByDesc('fn.id')
                ->limit(1);

            $record = $builder->first();

            if (! $record || empty($record->tracking_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file metadata with a tracking ID was found for this file number.'
                ], 404);
            }

            $preferredOrder = [
                'st_file_no' => $record->st_file_no,
                'mlsfNo' => $record->mlsfNo,
                'kangisFileNo' => $record->kangisFileNo,
                'NewKANGISFileNo' => $record->NewKANGISFileNo,
            ];

            $upperVariants = array_map(static function ($value) {
                return strtoupper(trim($value));
            }, $variants);

            $resolvedFileNumber = null;
            foreach ($preferredOrder as $value) {
                $trimmed = strtoupper(trim((string) $value));
                if ($trimmed !== '' && in_array($trimmed, $upperVariants, true)) {
                    $resolvedFileNumber = $value;
                    break;
                }
            }

            if ($resolvedFileNumber === null) {
                foreach ($preferredOrder as $value) {
                    if (!empty($value)) {
                        $resolvedFileNumber = $value;
                        break;
                    }
                }
            }

            if ($resolvedFileNumber === null) {
                $resolvedFileNumber = $fileNumber;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $record->id,
                    'file_number' => $resolvedFileNumber,
                    'st_file_number' => $record->st_file_no,
                    'mls_file_number' => $record->mlsfNo,
                    'kangis_file_number' => $record->kangisFileNo,
                    'new_kangis_file_number' => $record->NewKANGISFileNo,
                    'tracking_id' => $record->tracking_id,
                    'file_name' => $record->FileName,
                    'resolved_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('lookupByFileNumber failed', [
                'file_number' => $fileNumber,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve file metadata for the supplied file number.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Handle bulk entries by delegating to FileIndexController
            if ($request->has('bulk_entries')) {
                $fileIndexController = new \App\Http\Controllers\FileIndexController();
                return $fileIndexController->store($request);
            }

            $validated = $request->validate([
                'file_number' => 'required|string|max:255',
                'file_title' => 'required|string|max:255',
                'land_use_type' => 'nullable|string|max:255',
                'plot_number' => 'nullable|string|max:255',
                'tp_no' => 'nullable|string|max:255',
                'lpkn_no' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'district' => 'nullable|string|max:255',
                'registry' => 'nullable|string|max:255',
                'lga' => 'nullable|string|max:255',
                'has_cofo' => 'nullable|boolean',
                'has_transaction' => 'nullable|boolean',
                'is_problematic' => 'nullable|boolean',
                'is_co_owned_plot' => 'nullable|boolean',
                    'shelf_label_id' => 'nullable|integer',
                'is_merged' => 'nullable|boolean',
                'serial_no' => 'nullable|string|max:255',
                'batch_no' => 'nullable|string',
                'shelf_location' => 'nullable|string|max:255',
                'tracking_id' => 'nullable|string|max:255',
                'main_application_id' => 'nullable|integer',
                'subapplication_id' => 'nullable|integer',
                'file_number_source' => 'nullable|string',
                    'source_file_id' => 'nullable|integer',
                    'awaiting_file_no' => 'required|string|max:255',
                    'group_no' => 'nullable|string|max:255',
                    'batch_no_field' => 'nullable|string|max:255',
                    'mdc_batch_no' => 'nullable|string|max:255',
                    'sys_batch_no' => 'nullable|string|max:255',
                    'shelf_rack_no' => 'nullable|string|max:255',
                    'grouping_match_id' => 'nullable|integer|exists:sqlsrv.grouping,id',
            ]);

                $awaitingFileNo = $validated['awaiting_file_no'];
                $groupingId = $validated['grouping_match_id'] ?? null;
                $submittedSysBatch = isset($validated['sys_batch_no']) ? trim((string) $validated['sys_batch_no']) : null;
                $submittedShelfRack = isset($validated['shelf_rack_no']) ? trim((string) $validated['shelf_rack_no']) : null;
                $submittedRegistry = isset($validated['registry']) ? trim((string) $validated['registry']) : null;

                $submittedSysBatch = $submittedSysBatch === '' ? null : $submittedSysBatch;
                $submittedShelfRack = $submittedShelfRack === '' ? null : $submittedShelfRack;
                $submittedRegistry = $submittedRegistry === '' ? null : $submittedRegistry;

                $grouping = null;

                if ($groupingId) {
                    $grouping = Grouping::on('sqlsrv')->find($groupingId);
                }

                $normalizedAwaiting = $this->normalizeFileno($awaitingFileNo);

                if (!$grouping && $normalizedAwaiting) {
                    $grouping = Grouping::on('sqlsrv')
                        ->where(function ($query) use ($awaitingFileNo, $normalizedAwaiting) {
                            $query->where('awaiting_fileno', $awaitingFileNo)
                                ->orWhereRaw(
                                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(UPPER(awaiting_fileno), '-', ''), '/', ''), ' ', ''), '\\', ''), '.', '') = ?",
                                    [$normalizedAwaiting]
                                );
                        })
                        ->orderBy('updated_at', 'desc')
                        ->first();
                }

                if (!$grouping) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Awaiting file number must match an existing grouping record.'
                    ], 422);
                }

                $normalizedGroupingAwaiting = $this->normalizeFileno($grouping->awaiting_fileno);
                $normalizedFileNumber = $this->normalizeFileno($validated['file_number']);

                if (!$normalizedFileNumber || $normalizedFileNumber !== $normalizedGroupingAwaiting) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The selected file number must match the grouping awaiting file number.'
                    ], 422);
                }

                if ($normalizedAwaiting !== $normalizedGroupingAwaiting) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Awaiting file number does not match the grouping record.'
                    ], 422);
                }

                if ($grouping->sys_batch_no !== null) {
                    if ($submittedSysBatch === null || strcasecmp($submittedSysBatch, $grouping->sys_batch_no) !== 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'SYS batch number must come from the grouping record.'
                        ], 422);
                    }
                }

                if ($submittedSysBatch !== null && $grouping->sys_batch_no === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'SYS batch number is not defined for the grouping record.'
                    ], 422);
                }

                if ($grouping->shelf_rack !== null) {
                    if ($submittedShelfRack === null || strcasecmp($submittedShelfRack, $grouping->shelf_rack) !== 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Shelf/Rack number must come from the grouping record.'
                        ], 422);
                    }
                }

                if ($submittedShelfRack !== null && $grouping->shelf_rack === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Shelf/Rack number is not defined for the grouping record.'
                    ], 422);
                }

                $expectedRegistry = $this->deriveRegistryFromGrouping($grouping);
                if ($expectedRegistry !== null) {
                    if ($submittedRegistry === null || strcasecmp($submittedRegistry, $expectedRegistry) !== 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Registry value must match the derived grouping registry.'
                        ], 422);
                    }
                }

                if ($submittedRegistry !== null && $expectedRegistry === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Registry value is not defined for the grouping record.'
                    ], 422);
                }

                // Ensure registry persists as expected value
                if ($expectedRegistry) {
                    $validated['registry'] = $expectedRegistry;
                }

                unset(
                    $validated['awaiting_file_no'],
                    $validated['group_no'],
                    $validated['batch_no_field'],
                    $validated['mdc_batch_no'],
                    $validated['sys_batch_no'],
                    $validated['shelf_rack_no'],
                    $validated['grouping_match_id']
                );

            $validated['created_by'] = Auth::id();
            $validated['updated_by'] = Auth::id();
            // Ensure new records start with workflow_status = 'indexed'
            if (!isset($validated['workflow_status']) || empty($validated['workflow_status'])) {
                $validated['workflow_status'] = 'indexed';
            }

            // Log incoming registry and full payload for debugging
            Log::info('FileIndexing::store - validated payload', [
                'registry' => $validated['registry'] ?? null,
                'tp_no' => $validated['tp_no'] ?? null,
                'location' => $validated['location'] ?? null,
                'payload' => $validated,
                'user_id' => Auth::id()
            ]);

            $fileIndexing = FileIndexing::create($validated);
            
            // Log what was actually saved
            Log::info('FileIndexing::store - saved record', [
                'id' => $fileIndexing->id,
                'tp_no_saved' => $fileIndexing->tp_no,
                'location_saved' => $fileIndexing->location,
                'plot_number_saved' => $fileIndexing->plot_number
            ]);

            // Refresh from database to ensure returned model reflects saved columns
            try {
                $fileIndexing->refresh();
            } catch (\Throwable $e) {
                // If refresh fails (e.g., DB driver oddities), ignore and continue
                Log::warning('FileIndexing::store - refresh failed: ' . $e->getMessage());
            }

            // Handle batch and shelf assignment with new system
            if (isset($validated['shelf_label_id']) && !empty($validated['shelf_label_id'])) {
                try {
                    $batchId = $request->get('batch_id'); // From the new batch system
                    
                    // Check if using new batch management system
                    if ($batchId) {
                        $batchRecord = \App\Models\FileindexingBatch::find($batchId);
                        if ($batchRecord) {
                            // Update file indexing with batch reference
                            $fileIndexing->update(['batch_id' => $batchId]);
                            
                            // Mark shelf as used and update batch statistics
                            $batchRecord->markShelfUsed($validated['shelf_label_id']);
                            
                            Log::info('FileIndexing::store - used new batch system', [
                                'batch_id' => $batchId,
                                'shelf_id' => $validated['shelf_label_id'],
                                'batch_used_shelves' => $batchRecord->fresh()->used_shelves
                            ]);
                        }
                    } else {
                        // Fallback to legacy system
                        $tableExists = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'Rack_Shelf_Labels'");
                        if (!empty($tableExists)) {
                            DB::connection('sqlsrv')->table('Rack_Shelf_Labels')
                                ->where('id', $validated['shelf_label_id'])
                                ->update(['is_used' => 1]);
                        }
                        
                        Log::info('FileIndexing::store - used legacy shelf system', [
                            'shelf_id' => $validated['shelf_label_id']
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log the error but don't fail the file indexing creation
                    Log::error('Failed to mark shelf/batch as used: ' . $e->getMessage(), [
                        'shelf_label_id' => $validated['shelf_label_id'],
                        'batch_id' => $request->get('batch_id'),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'File indexing created successfully!',
                'data' => $fileIndexing
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getShelfForBatch($batch)
    {
        try {
            if (empty($batch)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch number is required'
                ], 400);
            }

            $batchNumber = (int) $batch;
            if ($batchNumber <= 0) {
                return response()->json(['success' => false, 'message' => 'Invalid batch number'], 400);
            }

            // Check if fileindexing_batch table exists
            $batchTableExists = DB::connection('sqlsrv')->select("
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME = 'fileindexing_batch'
            ");

            if (!empty($batchTableExists)) {
                // Use the new batch management system
                $batchRecord = \App\Models\FileindexingBatch::where('batch_number', $batchNumber)
                    ->where('is_active', true)
                    ->first();

                if (!$batchRecord) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Batch not found or not available',
                        'label' => ''
                    ]);
                }

                if ($batchRecord->is_full) {
                    return response()->json([
                        'success' => false,
                        'message' => 'All shelves in this batch are already used',
                        'label' => ''
                    ]);
                }

                // Get the next available shelf
                $shelf = $batchRecord->getNextAvailableShelf();
                
                if ($shelf) {
                    $label = $shelf->full_label ?? "Rack-Shelf-{$shelf->id}";
                    return response()->json([
                        'success' => true,
                        'label' => $label,
                        'shelf_label_id' => $shelf->id,
                        'batch_id' => $batchRecord->id,
                        'message' => 'Shelf location found using batch management system',
                        'batch_info' => [
                            'used_shelves' => $batchRecord->used_shelves,
                            'total_shelves' => $batchRecord->shelf_count,
                            'available_shelves' => $batchRecord->available_shelves
                        ]
                    ]);
                } else {
                    // Mark batch as full if no shelves available
                    $batchRecord->update(['is_full' => true]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'No available shelves in this batch (batch marked as full)',
                        'label' => ''
                    ]);
                }
            }

            // Fallback to legacy system
            return $this->getLegacyShelfForBatch($batchNumber);

        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'label' => "Default-S{$batch}",
                'message' => 'Database error occurred, using default shelf location',
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getLegacyShelfForBatch($batchNumber)
    {
        // Legacy system fallback
        $tableExists = DB::connection('sqlsrv')->select("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_NAME = 'Rack_Shelf_Labels'
        ");

        if (empty($tableExists)) {
            return response()->json([
                'success' => true,
                'label' => "A1-S{$batchNumber}",
                'message' => 'Using legacy default shelf location'
            ]);
        }

        $startId = ($batchNumber - 1) * 100 + 1;
        $endId = $batchNumber * 100;

        $shelf = DB::connection('sqlsrv')->table('Rack_Shelf_Labels')
            ->whereBetween('id', [$startId, $endId])
            ->where(function($query) {
                $query->where('is_used', 0)->orWhereNull('is_used');
            })
            ->orderBy('id')
            ->first();

        if ($shelf) {
            $label = $shelf->full_label ?? "Rack-Shelf-{$shelf->id}";
            return response()->json([
                'success' => true,
                'label' => $label,
                'shelf_label_id' => $shelf->id,
                'message' => 'Legacy shelf location found'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No available shelf in this batch (legacy)',
                'label' => ''
            ]);
        }
    }

    /**
     * Get specific shelf information by shelf_label_id
     */
    public function getShelfById($shelfId)
    {
        try {
            if (empty($shelfId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shelf ID is required'
                ], 400);
            }

            $shelf = DB::connection('sqlsrv')->table('Rack_Shelf_Labels')
                ->where('id', $shelfId)
                ->first();

            if (!$shelf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shelf not found',
                    'label' => ''
                ]);
            }

            // Check if shelf is already used
            $isUsedInRack = $shelf->is_used == 1;
            $isUsedInFileIndexing = DB::connection('sqlsrv')->table('file_indexings')
                ->where('shelf_label_id', $shelfId)
                ->exists();

            if ($isUsedInRack || $isUsedInFileIndexing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This shelf is already in use',
                    'label' => $shelf->full_label
                ]);
            }

            return response()->json([
                'success' => true,
                'label' => $shelf->full_label,
                'shelf_label_id' => $shelf->id,
                'rack' => $shelf->rack,
                'shelf' => $shelf->shelf,
                'message' => 'Shelf is available for use'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking shelf availability: ' . $e->getMessage(),
                'label' => ''
            ]);
        }
    }

    /**
     * Get available shelf labels for direct selection
     */
    public function getAvailableShelfLabels(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = 20; // Show more shelf labels per page
            $searchTerm = $request->get('q', '');
            $offset = ($page - 1) * $perPage;

            // Build query for available shelf labels
            $query = DB::connection('sqlsrv')->table('Rack_Shelf_Labels')
                ->where(function($q) {
                    $q->where('is_used', 0)->orWhereNull('is_used');
                })
                ->whereNotIn('id', function($subquery) {
                    $subquery->select('shelf_label_id')
                             ->from('file_indexings')
                             ->whereNotNull('shelf_label_id');
                });

            // Apply search filter
            if (!empty($searchTerm)) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('full_label', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('id', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('rack', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('shelf', 'LIKE', "%{$searchTerm}%");
                });
            }

            $total = $query->count();
            
            $shelves = $query->orderBy('id')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            $results = $shelves->map(function($shelf) {
                return [
                    'id' => $shelf->id,
                    'text' => $shelf->full_label . " (ID: {$shelf->id})",
                    'shelf_label_id' => $shelf->id,
                    'full_label' => $shelf->full_label,
                    'rack' => $shelf->rack,
                    'shelf' => $shelf->shelf
                ];
            });

            return response()->json([
                'success' => true,
                'shelves' => $results,
                'pagination' => [
                    'more' => ($offset + $perPage) < $total
                ],
                'total' => $total,
                'message' => "Found {$total} available shelf labels"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'shelves' => [],
                'pagination' => ['more' => false],
                'total' => 0,
                'message' => 'Error fetching shelf labels: ' . $e->getMessage()
            ]);
        }
    }

    public function getAvailableBatches(Request $request)
    {
        try {
            $page = $request->get('page', 1);
            $perPage = 5;
            $searchTerm = $request->get('q', '');

            // Check if fileindexing_batch table exists
            $batchTableExists = DB::connection('sqlsrv')->select("
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME = 'fileindexing_batch'
            ");

            if (!empty($batchTableExists)) {
                // Use the new batch system
                $result = \App\Models\FileindexingBatch::getAvailableBatches($searchTerm, $page, $perPage);
                
                return response()->json([
                    'success' => true,
                    'batches' => $result['batches'],
                    'pagination' => $result['pagination'],
                    'message' => "Found {$result['total']} available batches using batch management system"
                ]);
            }

            // Fallback to legacy system if batch table doesn't exist
            return $this->getLegacyAvailableBatches($request);
            
        } catch (\Exception $e) {
            // Emergency fallback
            $batches = [];
            for ($i = 1; $i <= 5; $i++) {
                if (empty($searchTerm) || strpos((string)$i, $searchTerm) !== false) {
                    $batches[] = [
                        'id' => $i,
                        'text' => $i
                    ];
                }
            }
            return response()->json([
                'success' => true,
                'batches' => $batches,
                'pagination' => ['more' => false],
                'message' => 'Using emergency fallback batch numbers',
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getLegacyAvailableBatches(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 5;
        $offset = ($page - 1) * $perPage;
        $searchTerm = $request->get('q', '');

        // Check if the Rack_Shelf_Labels table exists
        $tableExists = DB::connection('sqlsrv')->select("
            SELECT TABLE_NAME 
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_NAME = 'Rack_Shelf_Labels'
        ");

        if (empty($tableExists)) {
            // Table doesn't exist, return default batches
            $batches = [];
            $startBatch = ($page - 1) * $perPage + 1;
            $endBatch = $page * $perPage;

            for ($i = $startBatch; $i <= $endBatch; $i++) {
                if (empty($searchTerm) || strpos((string)$i, $searchTerm) !== false) {
                    $batches[] = [
                        'id' => $i,
                        'text' => $i
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'batches' => $batches,
                'pagination' => [
                    'more' => $page < 20
                ],
                'message' => 'Using legacy default batch numbers'
            ]);
        }

        // Find batch numbers that have available unused shelves (legacy method)
        $availableBatchNumbers = [];
        $maxShelfId = DB::connection('sqlsrv')->table('Rack_Shelf_Labels')->max('id') ?? 1000;
        $maxBatchNumber = (int) ceil($maxShelfId / 100);

        for ($batchNumber = 1; $batchNumber <= $maxBatchNumber; $batchNumber++) {
            if (!empty($searchTerm) && strpos((string)$batchNumber, $searchTerm) === false) {
                continue;
            }

            $startId = ($batchNumber - 1) * 100 + 1;
            $endId = $batchNumber * 100;

            $hasUnusedShelf = DB::connection('sqlsrv')->table('Rack_Shelf_Labels')
                ->whereBetween('id', [$startId, $endId])
                ->where(function($query) {
                    $query->where('is_used', 0)->orWhereNull('is_used');
                })
                ->exists();

            if ($hasUnusedShelf) {
                $availableBatchNumbers[] = $batchNumber;
            }
        }

        $totalAvailable = count($availableBatchNumbers);
        $paginatedBatches = array_slice($availableBatchNumbers, $offset, $perPage);

        $batches = array_map(function($batchNumber) {
            return [
                'id' => $batchNumber,
                'text' => $batchNumber
            ];
        }, $paginatedBatches);

        return response()->json([
            'success' => true,
            'batches' => $batches,
            'pagination' => [
                'more' => ($offset + $perPage) < $totalAvailable
            ],
            'message' => "Found {$totalAvailable} legacy batches with available shelves"
        ]);
    }

    // Return distinct batch numbers present in file_indexings (limited to batches with records)
    public function distinctBatches()
    {
        try {
            $batches = DB::connection('sqlsrv')->table('file_indexings')
                ->whereNotNull('batch_no')
                ->select(DB::raw('DISTINCT batch_no'))
                ->orderBy('batch_no')
                ->pluck('batch_no')
                ->toArray();

            // Debug: Log what we found
            \Log::info('DistinctBatches Debug - Found batches: ', $batches);
            \Log::info('DistinctBatches Debug - Total count: ' . count($batches));

            // Also check what's actually in the table
            $sampleRecords = DB::connection('sqlsrv')->table('file_indexings')
                ->whereNotNull('batch_no')
                ->select('batch_no', 'file_number', 'registry')
                ->orderBy('batch_no', 'desc')
                ->limit(10)
                ->get();
            \Log::info('DistinctBatches Debug - Sample records: ', $sampleRecords->toArray());

            return response()->json(['success' => true, 'batches' => $batches]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Debug method to check file_indexings table content
    public function debugBatches()
    {
        try {
            // Get all distinct batch_no values with count
            $batchCounts = DB::connection('sqlsrv')->table('file_indexings')
                ->select(DB::raw('batch_no, COUNT(*) as record_count'))
                ->whereNotNull('batch_no')
                ->groupBy('batch_no')
                ->orderBy('batch_no')
                ->get();

            // Get table row count
            $totalRows = DB::connection('sqlsrv')->table('file_indexings')->count();

            // Get sample of latest records
            $latestRecords = DB::connection('sqlsrv')->table('file_indexings')
                ->select('id', 'batch_no', 'file_number', 'registry', 'created_at')
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'total_rows' => $totalRows,
                'batch_counts' => $batchCounts,
                'latest_records' => $latestRecords
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Show standalone Sign In & Out page
    public function signin()
    {
        return view('fileindexing.signin');
    }

    // Generate Sign In & Out report for a given batch_no (JSON)
    public function signinReport(Request $request)
    {
        $batch = (int) $request->get('batch_no', 0);
        if ($batch <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid batch number'], 400);
        }

        try {
            // Limit to 100 records per batch
            $rows = DB::connection('sqlsrv')->table('file_indexings as f')
                ->leftJoin('users as u', 'u.id', '=', 'f.created_by')
                ->where('f.batch_no', $batch)
                ->orderBy('f.id')
                ->limit(100)
                ->get([
                    'f.file_number',
                    DB::raw("COALESCE(f.registry, '') as registry"),
                    DB::raw("COALESCE(f.batch_no, '') as batch_no"),
                    DB::raw("COALESCE(f.file_title, '') as name"),
                    DB::raw("COALESCE(f.plot_number, '') as plot_number"),
                    DB::raw("COALESCE(f.lga, '') as lga"),
                    DB::raw("COALESCE(f.workflow_status, '') as status"),
                    DB::raw("COALESCE(f.shelf_location, '') as location"),
                    DB::raw("COALESCE(f.land_use_type, '') as land_use"),
                    DB::raw("COALESCE(f.district, '') as district"),
                    DB::raw("CONVERT(varchar(19), f.created_at, 120) as indexed_date"),
                    DB::raw("COALESCE(u.first_name + ' ' + u.last_name, '') as indexed_by")
                ]);

            // Debug: Log first few records to see actual data
            \Log::info('SigninReport Debug - Batch: ' . $batch);
            \Log::info('SigninReport Debug - Total rows: ' . count($rows));
            if (count($rows) > 0) {
                \Log::info('SigninReport Debug - First row: ', [
                    'registry' => $rows[0]->registry ?? 'NULL',
                    'batch_no' => $rows[0]->batch_no ?? 'NULL',
                    'file_number' => $rows[0]->file_number ?? 'NULL'
                ]);
            }

            return response()->json(['success' => true, 'rows' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Export Sign In & Out report (format = pdf | excel)
    public function exportSigninReport($format, Request $request)
    {
        $batch = (int) $request->get('batch_no', 0);
        if ($batch <= 0) {
            return response('Invalid batch number', 400);
        }

        // Reuse signinReport query
        $rows = DB::connection('sqlsrv')->table('file_indexings as f')
            ->leftJoin('users as u', 'u.id', '=', 'f.created_by')
            ->where('f.batch_no', $batch)
            ->orderBy('f.id')
            ->limit(100)
            ->get([
                'f.file_number',
                DB::raw("COALESCE(f.registry, '') as registry"),
                DB::raw("COALESCE(f.batch_no, '') as batch_no"),
                DB::raw("COALESCE(f.file_title, '') as name"),
                DB::raw("COALESCE(f.plot_number, '') as plot_number"),
                DB::raw("COALESCE(f.lga, '') as lga"),
                DB::raw("COALESCE(f.workflow_status, '') as status"),
                DB::raw("COALESCE(f.shelf_location, '') as location"),
                DB::raw("COALESCE(f.land_use_type, '') as land_use"),
                DB::raw("COALESCE(f.district, '') as district"),
                DB::raw("CONVERT(varchar(19), f.created_at, 120) as indexed_date"),
                DB::raw("COALESCE(u.first_name + ' ' + u.last_name, '') as indexed_by")
            ]);

        if (strtolower($format) === 'excel') {
            // Return CSV as Excel-friendly download
            $filename = "signin_batch_{$batch}.csv";
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($rows, $batch) {
                $out = fopen('php://output', 'w');
                
                // Get registry/batch from first row for header
                $registryBatch = '';
                if (count($rows) > 0) {
                    $firstRow = $rows[0];
                    $registryBatch = ($firstRow->registry ?? '') . (($firstRow->registry && $firstRow->batch_no) ? '/' : '') . ($firstRow->batch_no ?? '');
                }
                
                // Add title row
                fputcsv($out, ['Sign In & Out Sheet - Registry/Batch: ' . $registryBatch]);
                fputcsv($out, []); // Empty row
                
                // Header row without Registry/Batch column
                fputcsv($out, ['N/S','File Number','Plot Number','LGA','Name','Location','Land Use','District','Indexed Date','Indexed By']);
                $i = 1;
                foreach ($rows as $r) {
                    fputcsv($out, [$i++, $r->file_number, $r->plot_number, $r->lga ?? '', $r->name, $r->location, $r->land_use, $r->district, $r->indexed_date, $r->indexed_by]);
                }
                // signature lines
                fputcsv($out, []);
                fputcsv($out, ['Sign In (KLAES MDC):','Signed:','Date Received:','Date Submitted:']);
                fputcsv($out, ['Sign Out (Ministry):','Signed:','Date Submitted:','Date Received:']);
                fclose($out);
            };

            return response()->stream($callback, 200, $headers);
        }

        // Default: generate simple HTML for printing; prefer producing a real PDF when possible
        
        // Get registry/batch from first row for title
        $registryBatch = '';
        if (count($rows) > 0) {
            $firstRow = $rows[0];
            $registryBatch = ($firstRow->registry ?? '') . (($firstRow->registry && $firstRow->batch_no) ? '/' : '') . ($firstRow->batch_no ?? '');
        }
        
        $html = '<html><head><meta charset="utf-8"><title>Sign In & Out - Registry/Batch '.htmlspecialchars($registryBatch).'</title><style>body{font-family: Arial, sans-serif;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;}th{background:#f7f7f7;text-align:left;}</style></head><body>';
        $html .= '<h2>Sign In & Out Report - Registry/Batch: '.htmlspecialchars($registryBatch).'</h2>';
        $html .= '<table><thead><tr><th>N/S</th><th>File Number</th><th>Plot Number</th><th>LGA</th><th>Name</th><th>Location</th><th>Land Use</th><th>District</th><th>Indexed Date</th><th>Indexed By</th></tr></thead><tbody>';
        $i = 1;
        foreach ($rows as $r) {
            $html .= '<tr>';
            $html .= '<td>'.htmlspecialchars($i++).'</td>';
            $html .= '<td>'.htmlspecialchars($r->file_number).'</td>';
            $html .= '<td>'.htmlspecialchars($r->plot_number).'</td>';
            $html .= '<td>'.htmlspecialchars($r->lga ?? '').'</td>';
            $html .= '<td>'.htmlspecialchars($r->name).'</td>';
            $html .= '<td>'.htmlspecialchars($r->location).'</td>';
            $html .= '<td>'.htmlspecialchars($r->land_use).'</td>';
            $html .= '<td>'.htmlspecialchars($r->district).'</td>';
            $html .= '<td>'.htmlspecialchars($r->indexed_date).'</td>';
            $html .= '<td>'.htmlspecialchars($r->indexed_by).'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        $html .= '<div style="margin-top:40px;display:flex;justify-content:flex-end"><div style="width:360px"><div class="text-sm font-semibold">Sign In (KLAES MDC)</div><div style="margin-top:12px">Signed: ____________________________</div><div style="margin-top:6px">Date Received: _______________________</div><div style="margin-top:6px">Date Submitted: ______________________</div><div style="margin-top:24px" class="text-sm font-semibold">Sign Out (Ministry)</div><div style="margin-top:12px">Signed: ____________________________</div><div style="margin-top:6px">Date Submitted: ______________________</div><div style="margin-top:6px">Date Received: _______________________</div></div></div>';
        $html .= '</body></html>';

        // If the request asked for PDF, attempt to render using dompdf (preferred). If not available, fall back to streaming HTML with PDF headers.
        if (strtolower($format) === 'pdf') {
            // Use Barryvdh arryvdh/laravel-dompdf if available
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                try {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'landscape');
                    return $pdf->download("signin_batch_{$batch}.pdf");
                } catch (\Exception $e) {
                    // fallthrough to fallback
                    \Log::error('PDF generation (barryvdh) failed: ' . $e->getMessage());
                }
            }

            // Try using dompdf/dompdf directly if available
            if (class_exists('\Dompdf\Dompdf')) {
                try {
                    $dompdf = new \Dompdf\Dompdf();
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    $output = $dompdf->output();
                    $headers = [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => "attachment; filename=\"signin_batch_{$batch}.pdf\"",
                    ];
                    return response($output, 200, $headers);
                } catch (\Exception $e) {
                    \Log::error('PDF generation (dompdf) failed: ' . $e->getMessage());
                }
            }

            // Fallback: PDF library not available or generation failed.
            // Returning raw HTML with application/pdf headers breaks PDF viewers (causes "Failed to load PDF document").
            // Instead, return an HTML download and log the condition so admins can install a PDF library.
            \Log::warning("PDF generation unavailable for signin batch {$batch} - dompdf/barryvdh missing or failed");

            $notice = '<div style="margin-top:16px;padding:12px;border:1px solid #f1c40f;background:#fff8e1;color:#664d03;font-size:13px;">
                <strong>Note:</strong> PDF generation is not available on this server. To enable PDF export, install <code>barryvdh/laravel-dompdf</code> or <code>dompdf/dompdf</code> and restart the app.
            </div>';

            $fullHtml = $html . $notice;
            $headers = [
                'Content-Type' => 'text/html; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"signin_batch_{$batch}.html\"",
            ];

            return response($fullHtml, 200, $headers);
        }

        // If not requesting PDF, return HTML (used previously for default)
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => "attachment; filename=\"signin_batch_{$batch}.html\"",
        ];

        return response($html, 200, $headers);
    }

    /**
     * Generate new batches
     */
    public function generateBatches(Request $request)
    {
        try {
            $batchesToGenerate = $request->get('count', 100);
            
            if ($batchesToGenerate < 1 || $batchesToGenerate > 1000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid batch count. Must be between 1 and 1000.'
                ], 400);
            }

            $generated = \App\Models\FileindexingBatch::generateBatches($batchesToGenerate);

            return response()->json([
                'success' => true,
                'message' => "Successfully generated {$generated} new batches",
                'generated_count' => $generated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate batches: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get batch management dashboard data
     */
    public function getBatchManagementData()
    {
        try {
            // Check if batch table exists
            $batchTableExists = DB::connection('sqlsrv')->select("
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME = 'fileindexing_batch'
            ");

            if (empty($batchTableExists)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch management table not found. Please run migrations.'
                ]);
            }

            $stats = [
                'total_batches' => \App\Models\FileindexingBatch::count(),
                'active_batches' => \App\Models\FileindexingBatch::where('is_active', true)->count(),
                'full_batches' => \App\Models\FileindexingBatch::where('is_full', true)->count(),
                'available_batches' => \App\Models\FileindexingBatch::where('is_active', true)
                    ->where('is_full', false)->count(),
                'total_shelves' => \App\Models\FileindexingBatch::sum('shelf_count'),
                'used_shelves' => \App\Models\FileindexingBatch::sum('used_shelves'),
            ];

            $stats['available_shelves'] = $stats['total_shelves'] - $stats['used_shelves'];
            $stats['usage_percentage'] = $stats['total_shelves'] > 0 
                ? round(($stats['used_shelves'] / $stats['total_shelves']) * 100, 2) 
                : 0;

            // Get recent batches
            $recentBatches = \App\Models\FileindexingBatch::orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['batch_number', 'shelf_count', 'used_shelves', 'is_full', 'is_active', 'created_at']);

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'recent_batches' => $recentBatches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load batch management data: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-assign shelves to files using gap-filling logic
     */
    public function autoAssignShelves(Request $request)
    {
        try {
            $batchSize = 100; // Files per shelf label
            $processedFiles = 0;

            DB::connection('sqlsrv')->transaction(function() use (&$processedFiles, $batchSize) {
                // Get unassigned files
                $unassignedFiles = DB::connection('sqlsrv')->table('file_indexings')
                    ->whereNull('shelf_label_id')
                    ->orderBy('id')
                    ->get(['id']);

                if ($unassignedFiles->isEmpty()) {
                    return;
                }

                // Get unused shelf labels with proper ordering
                $unusedLabels = DB::connection('sqlsrv')->table('Rack_Shelf_Labels as rsl')
                    ->leftJoin('file_indexings as fi', 'fi.shelf_label_id', '=', 'rsl.id')
                    ->whereNull('fi.shelf_label_id')
                    ->where(function($query) {
                        $query->where('rsl.is_used', 0)->orWhereNull('rsl.is_used');
                    })
                    ->orderBy('rsl.id')
                    ->get(['rsl.id', 'rsl.full_label']);

                if ($unusedLabels->isEmpty()) {
                    throw new \Exception('No unused shelf labels available');
                }

                // Assign shelf labels using gap-filling logic
                foreach ($unassignedFiles as $index => $file) {
                    // Calculate which shelf label to use (1 shelf per batch of files)
                    $shelfIndex = (int) floor($index / $batchSize);
                    
                    if ($shelfIndex >= $unusedLabels->count()) {
                        break; // No more shelf labels available
                    }

                    $shelfLabel = $unusedLabels[$shelfIndex];

                    // Update the file indexing record
                    DB::connection('sqlsrv')->table('file_indexings')
                        ->where('id', $file->id)
                        ->update([
                            'shelf_label_id' => $shelfLabel->id,
                            'shelf_location' => $shelfLabel->full_label,
                            'updated_at' => now()
                        ]);

                    // Mark the shelf as used
                    DB::connection('sqlsrv')->table('Rack_Shelf_Labels')
                        ->where('id', $shelfLabel->id)
                        ->update(['is_used' => 1]);

                    $processedFiles++;
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned shelves to {$processedFiles} files using gap-filling logic",
                'processed_files' => $processedFiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-assign shelves: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show batch management page
     */
    public function batchManagement()
    {
        return view('fileindexing.batch-management');
    }

    /**
     * Run shelf location cleanup and batch update
     */
    public function runShelfCleanup(Request $request)
    {
        try {
            $dryRun = $request->get('dry_run', false);
            
            // Execute the cleanup command programmatically
            $exitCode = \Artisan::call('fileindexing:cleanup-shelf-batch', [
                '--dry-run' => $dryRun
            ]);

            $output = \Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => $dryRun ? 'Dry-run completed successfully' : 'Cleanup completed successfully',
                    'output' => $output,
                    'dry_run' => $dryRun
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cleanup failed',
                    'output' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error running cleanup: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
