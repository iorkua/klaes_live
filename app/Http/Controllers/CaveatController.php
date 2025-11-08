<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use App\Models\Caveat;
use App\Models\InstrumentType;

class CaveatController extends Controller
{
    /**
     * Cached column listings per table to avoid repeated INFORMATION_SCHEMA lookups.
     * @var array<string, array<string>>
     */
    protected array $tableColumnCache = [];

    public function index()
    {
        $PageTitle = "Caveat Records";
        $PageDescriptionS = "";

        // Get active instrument types for the dropdown
        $instrumentTypes = InstrumentType::active()
            ->select('InstrumentTypeID', 'InstrumentName', 'Description')
            ->orderBy('InstrumentName')
            ->get();

        return view('caveat.index', compact('PageTitle', 'PageDescriptionS', 'instrumentTypes'));
    }

    // Create/place a new caveat
    public function store(Request $request)
    {
        $validated = $request->validate([
            'encumbrance_type' => 'required|string|max:100',
            'instrument_type_id' => 'nullable|integer|exists:sqlsrv.InstrumentTypes,InstrumentTypeID',
            'file_number' => 'required|string|max:100', // Made required for business rule validation
            'file_number_id' => 'nullable|integer', // FK to fileNumber or file_numbers
            'file_number_type' => 'nullable|string|max:50',
            'location' => 'required|string|max:255',
            'petitioner' => 'required|string|max:255',
            'petitioner_address' => 'nullable|string|max:500',
            'grantee' => 'nullable|string|max:255', // Frontend field name
            'grantee_address' => 'nullable|string|max:500',
            'serial_no' => 'nullable|integer|min:1',
            'page_no' => 'nullable|integer|min:1',
            'volume_no' => 'nullable|integer|min:1',
            'registration_number' => 'nullable|string|max:50',
            'start_date' => 'required|date',
            'release_date' => 'nullable|date',
            'instructions' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        // Business Rule: Check if record exists in any of the 3 tables before allowing caveat
        $fileNumber = $validated['file_number'];
        $recordExists = $this->checkRecordExists($fileNumber);
        
        if (!$recordExists) {
            return response()->json([
                'success' => false,
                'error' => 'Record not found. Please create the record first before placing a caveat.',
                'error_type' => 'record_not_found'
            ], 422);
        }

        // Business Rule: Check for duplicate caveat placement
        $duplicateCheck = $this->checkDuplicateCaveat($fileNumber, $validated);
        
        if ($duplicateCheck['isDuplicate']) {
            return response()->json([
                'success' => false,
                'error' => $duplicateCheck['message'],
                'error_type' => 'duplicate_caveat',
                'existing_caveat' => $duplicateCheck['existingCaveat']
            ], 422);
        }

        // Resolve file_number -> id/type if not provided
        if (empty($validated['file_number_id']) && !empty($validated['file_number'])) {
            [$resolvedId, $resolvedType] = $this->resolveFileNumber($validated['file_number']);
            if ($resolvedId) {
                $validated['file_number_id'] = $resolvedId;
            }
            if ($resolvedType && empty($validated['file_number_type'])) {
                $validated['file_number_type'] = $resolvedType;
            }
        }

        // Store file number in appropriate field based on type
        $this->setFileNumberFields($validated, $fileNumber);

        // Generate caveat number if not provided
        $caveatNumber = $request->input('caveat_number');
        if (!$caveatNumber) {
            $caveatNumber = 'CAV-' . date('Y') . '-' . str_pad((string) (Caveat::count() + 1), 4, '0', STR_PAD_LEFT);
        }

        DB::connection('sqlsrv')->beginTransaction();
        try {
            $caveat = new Caveat();
            
            // Map frontend field names to database column names
            $caveatData = $validated;
            if (isset($caveatData['grantee'])) {
                $caveatData['grantee_name'] = $caveatData['grantee'];
                unset($caveatData['grantee']); // Remove the frontend field name
            }
            
            $caveat->fill($caveatData);
            $caveat->caveat_number = $caveatNumber;
            $caveat->status = $request->input('status', 'active');
            $caveat->created_by = Auth::user()->name ?? 'System';
            $caveat->updated_by = Auth::user()->name ?? 'System';

            // Default page_no to serial_no if not provided
            if (empty($caveat->page_no) && !empty($caveat->serial_no)) {
                $caveat->page_no = $caveat->serial_no;
            }

            // Compose registration number if parts are present and not provided
            if (empty($caveat->registration_number) && $caveat->serial_no && $caveat->page_no && $caveat->volume_no) {
                $caveat->registration_number = $caveat->serial_no . '/' . $caveat->page_no . '/' . $caveat->volume_no;
            }

            $caveat->save();

            // Apply caveated flags to related tables and set caveat_id
            if (!empty($validated['file_number'])) {
                $comment = 'Caveated via ' . ($validated['encumbrance_type'] ?? 'encumbrance') . ' (Caveat No: ' . $caveat->caveat_number . ') on ' . date('Y-m-d');
                $this->setCaveatedFlags($validated['file_number'], true, $comment, $caveat->id);
            }

            DB::connection('sqlsrv')->commit();
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $caveat,
        ]);
    }

    // Search/list caveats with filters
    public function indexApi(Request $request)
    {
        $query = Caveat::query();

        // Filters
        if ($request->filled('q')) {
            $search = '%' . $request->input('q') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('caveat_number', 'like', $search)
                  ->orWhere('registration_number', 'like', $search)
                  ->orWhere('petitioner', 'like', $search)
                  ->orWhere('grantee_name', 'like', $search)
                  ->orWhere('location', 'like', $search)
                  ->orWhere('encumbrance_type', 'like', $search);
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('start_date', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('start_date', '<=', $request->input('to_date'));
        }

        $caveats = $query->orderByDesc('start_date')->limit(200)->get();

        return response()->json([
            'success' => true,
            'data' => $caveats,
            'count' => $caveats->count(),
        ]);
    }

    // Get a single caveat
    public function show($id)
    {
        $caveat = Caveat::findOrFail($id);
        return response()->json(['success' => true, 'data' => $caveat]);
    }

    // Get caveat statistics
    public function stats()
    {
        try {
            $total = Caveat::count();
            $active = Caveat::where('status', 'active')->count();
            $released = Caveat::where('status', 'released')->count();
            $lifted = Caveat::where('status', 'lifted')->count();
            $draft = Caveat::where('status', 'draft')->count();
            $expired = Caveat::where('status', 'expired')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'active' => $active,
                    'released' => $released,
                    'lifted' => $lifted,
                    'draft' => $draft,
                    'expired' => $expired
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load statistics',
                'data' => [
                    'total' => 0,
                    'active' => 0,
                    'released' => 0,
                    'lifted' => 0,
                    'draft' => 0,
                    'expired' => 0
                ]
            ]);
        }
    }

    // Lift (release) a caveat: set status and release fields
    public function lift(Request $request, $id)
    {
        $caveat = Caveat::findOrFail($id);

        $validated = $request->validate([
            'release_date' => 'required|date',
            'lift_instructions' => 'nullable|string',
            'lift_remarks' => 'nullable|string',
        ]);

        DB::connection('sqlsrv')->beginTransaction();
        try {
            $caveat->release_date = $validated['release_date'];
            // Append lifting notes into main narrative fields (simple model; can be normalized later)
            if (!empty($validated['lift_instructions'])) {
                $caveat->instructions = trim(($caveat->instructions ? $caveat->instructions . "\n\n" : '') . 'LIFT INSTRUCTIONS: ' . $validated['lift_instructions']);
            }
            if (!empty($validated['lift_remarks'])) {
                $caveat->remarks = trim(($caveat->remarks ? $caveat->remarks . "\n\n" : '') . 'LIFT REMARKS: ' . $validated['lift_remarks']);
            }
            $caveat->status = 'released';
            $caveat->updated_by = Auth::user()->name ?? 'System';
            $caveat->save();

            // Try to load file number string from fileNumber tables using stored id
            $fileNo = null;
            if (!empty($caveat->file_number_id)) {
                // Try dbo.fileNumber first
                try {
                    $row = DB::connection('sqlsrv')->table('fileNumber')->where('id', $caveat->file_number_id)->first();
                    if ($row) {
                        $fileNo = $row->kangisFileNo ?: ($row->mlsfNo ?: ($row->NewKANGISFileNo ?: null));
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
                // Fallback to file_numbers
                if (!$fileNo) {
                    try {
                        $row = DB::connection('sqlsrv')->table('file_numbers')->where('id', $caveat->file_number_id)->first();
                        if ($row) {
                            $fileNo = $row->file_number;
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }

            if ($fileNo) {
                $comment = 'Caveat lifted (Caveat No: ' . $caveat->caveat_number . ') on ' . date('Y-m-d');
                $this->setCaveatedFlags($fileNo, false, $comment, null);
            }

            DB::connection('sqlsrv')->commit();
        } catch (\Throwable $e) {
            DB::connection('sqlsrv')->rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'data' => $caveat]);
    }

    private function resolveFileNumber(string $fileNo): array
    {
        $conn = DB::connection('sqlsrv');
        $variants = $this->buildFileNumberVariants($fileNo);
        // Try dbo.fileNumber table (preferred)
        try {
            $query = $conn->table('fileNumber')
                ->select('id', 'kangisFileNo', 'mlsfNo', 'NewKANGISFileNo', 'st_file_no');
            $this->applyFileNumberFilter($query, ['kangisFileNo', 'mlsfNo', 'NewKANGISFileNo', 'st_file_no'], $variants);
            $row = $query->first();
            if ($row) {
                $type = null;
                if (!empty($row->st_file_no)) {
                    $type = 'ST';
                } elseif (!empty($row->kangisFileNo)) {
                    $type = 'KANGIS';
                } elseif (!empty($row->NewKANGISFileNo)) {
                    $type = 'New KANGIS';
                } else {
                    $type = 'MLSF';
                }
                return [$row->id, $type];
            }
        } catch (\Throwable $e) {
            // ignore
        }
        // Fallback to file_numbers table
        try {
            $query = $conn->table('file_numbers')->select('id', 'file_type', 'file_number');
            $this->applyFileNumberFilter($query, ['file_number'], $variants);
            $row = $query->first();
            if ($row) {
                return [$row->id, $row->file_type];
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return [null, null];
    }

    private function setCaveatedFlags(string $fileNo, bool $isCaveated, string $comment, ?int $caveatId = null): void
    {
        $conn = DB::connection('sqlsrv');
        $variants = $this->buildFileNumberVariants($fileNo);

        // property_records: match on common file number columns
        try {
            $query = $conn->table('property_records');
            if ($this->applyFileNumberFilterForTable($query, 'property_records', ['mlsFNo', 'kangisFileNo', 'NewKANGISFileno'], $variants, 'setFlags.property_records')) {
                $query
                    ->update([
                        'is_caveated' => $isCaveated ? 1 : 0,
                        'caveat_id' => $caveatId,
                        'caveated_comment' => DB::raw($isCaveated
                            ? "CASE WHEN caveated_comment IS NULL OR LTRIM(RTRIM(caveated_comment)) = '' THEN '" . str_replace("'", "''", $comment) . "' ELSE caveated_comment + CHAR(13)+CHAR(10) + '" . str_replace("'", "''", $comment) . "' END"
                            : "CASE WHEN caveated_comment IS NULL THEN NULL ELSE caveated_comment + CHAR(13)+CHAR(10) + '" . str_replace("'", "''", $comment) . "' END"
                        ),
                    ]);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // registered_instruments: try common columns
        try {
            $query = $conn->table('registered_instruments');
            if ($this->applyFileNumberFilterForTable($query, 'registered_instruments', ['file_number', 'file_no', 'fileno', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo'], $variants, 'setFlags.registered_instruments')) {
                $query->update([
                    'is_caveated' => $isCaveated ? 1 : 0,
                    'caveat_id' => $caveatId,
                    'caveated_comment' => DB::raw($isCaveated
                        ? "CASE WHEN caveated_comment IS NULL OR LTRIM(RTRIM(caveated_comment)) = '' THEN '" . str_replace("'", "''", $comment) . "' ELSE caveated_comment + CHAR(13)+CHAR(10) + '" . str_replace("'", "''", $comment) . "' END"
                        : "CASE WHEN caveated_comment IS NULL THEN NULL ELSE caveated_comment + CHAR(13)+CHAR(10) + '" . str_replace("'", "''", $comment) . "' END"
                    ),
                ]);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // CofO: try multiple possible table names and columns
        foreach (['CofO', 'cofo', 'CoFO', 'COFO'] as $tbl) {
            try {
                $exists = $conn->selectOne("SELECT 1 AS e FROM sys.tables WHERE name = ?", [$tbl]);
                if ($exists) {
                    $query = $conn->table($tbl);
                    if ($this->applyFileNumberFilterForTable($query, $tbl, ['file_no', 'FileNo', 'NewFileNo', 'mlsFNo', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo', 'NewKANGISFileno', 'np_fileno', 'cofo_no'], $variants, 'setFlags.cofo')) {
                        $query
                            ->update([
                                'is_caveated' => $isCaveated ? 1 : 0,
                                'caveat_id' => $caveatId,
                                'caveated_comment' => DB::raw($isCaveated
                                    ? "CASE WHEN caveated_comment IS NULL OR LTRIM(RTRIM(caveated_comment)) = '' THEN '" . str_replace("'", "''", $comment) . "' ELSE caveated_comment + CHAR(13)+CHAR(10) + '" . str_replace("'", "''", $comment) . "' END"
                                    : "CASE WHEN caveated_comment IS NULL THEN NULL ELSE caveated_comment + CHAR(13)+CHAR(10) + '" . str_replace("'", "''", $comment) . "' END"
                                ),
                            ]);
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    /**
     * Check if a record exists in any of the 3 tables (property_records, CofO, registered_instruments)
     */
    private function checkRecordExists(string $fileNo): bool
    {
        $conn = DB::connection('sqlsrv');
        $variants = $this->buildFileNumberVariants($fileNo);

        try {
            $query = $conn->table('property_records')->limit(1);
            if ($this->applyFileNumberFilterForTable($query, 'property_records', ['mlsFNo', 'kangisFileNo', 'NewKANGISFileno'], $variants, 'exists.property_records') && $query->exists()) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $query = $conn->table('registered_instruments')->limit(1);
            if ($this->applyFileNumberFilterForTable($query, 'registered_instruments', ['file_number', 'file_no', 'fileno', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo'], $variants, 'exists.registered_instruments') && $query->exists()) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        foreach (['CofO', 'cofo', 'CoFO', 'COFO'] as $tbl) {
            try {
                $tableExists = $conn->selectOne("SELECT 1 AS e FROM sys.tables WHERE name = ?", [$tbl]);
                if (! $tableExists) {
                    continue;
                }

                $query = $conn->table($tbl)->limit(1);
                if ($this->applyFileNumberFilterForTable($query, $tbl, ['file_no', 'FileNo', 'NewFileNo', 'mlsFNo', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo', 'NewKANGISFileno', 'np_fileno', 'cofo_no'], $variants, 'exists.cofo') && $query->exists()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return false;
    }

    /**
     * Search for file number across property_records, registered_instruments, and CofO tables
     */
    public function searchFileNumber(Request $request)
    {
        $fileNumber = trim((string) $request->input('file_number'));

        if ($fileNumber === '') {
            return response()->json([
                'success' => false,
                'error' => 'File number is required'
            ]);
        }

        try {
            // Log the search attempt
            \Log::info('Caveat: Searching for file number', ['file_number' => $fileNumber]);
            
            $results = $this->collectFileNumberSources($fileNumber);
            
            // Log results
            \Log::info('Caveat: Search results', [
                'file_number' => $fileNumber,
                'found' => $results['found'],
                'primary_source' => $results['primary_source'],
                'sources_count' => count($results['sources'])
            ]);

            if (empty($results['sources'])) {
                return response()->json([
                    'success' => true,
                    'found' => false,
                    'data' => null,
                    'table' => null,
                    'primary_source' => null,
                    'sources' => [],
                    'normalized' => ['fields' => [], 'source_order' => []]
                ]);
            }

            $primarySource = $results['primary_source'];
            $primaryPayload = $primarySource && isset($results['sources'][$primarySource])
                ? $results['sources'][$primarySource]
                : null;

            return response()->json([
                'success' => true,
                'found' => $results['found'],
                'data' => $primaryPayload['raw'] ?? null,
                'table' => $primaryPayload['table'] ?? $primarySource,
                'primary_source' => $primarySource,
                'sources' => $results['sources'],
                'normalized' => $results['normalized']
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gather file number information across supported tables and return normalized view.
     */
    protected function collectFileNumberSources(string $fileNumber): array
    {
        $conn = DB::connection('sqlsrv');
        $sources = [];
        $found = false;
        $variants = $this->buildFileNumberVariants($fileNumber);
        
        \Log::info('Caveat: Built file number variants', [
            'input' => $fileNumber,
            'variants_count' => count($variants),
            'variants_sample' => array_slice($variants, 0, 5)
        ]);

        // property_records has highest priority
        try {
            $recordQuery = $conn->table('property_records');
            if (! $this->applyFileNumberFilterForTable($recordQuery, 'property_records', ['mlsFNo', 'kangisFileNo', 'NewKANGISFileno'], $variants, 'collect.property_records')) {
                \Log::debug('Caveat: Skipping property_records search due to missing columns', ['table' => 'property_records']);
            }
            $record = $recordQuery
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            if ($record) {
                $found = true;
                $raw = $this->convertRecordToArray($record);
                $sources['property_records'] = [
                    'table' => 'property_records',
                    'raw' => $raw,
                    'fields' => $this->normalizePropertyRecord($raw),
                    'priority' => 90,
                ];
                \Log::info('Caveat: Found in property_records', ['record_id' => $record->id ?? 'unknown']);
            }
        } catch (\Throwable $e) {
            \Log::warning('Caveat: Error searching property_records', ['error' => $e->getMessage()]);
        }

        // registered_instruments next
        try {
            $recordQuery = $conn->table('registered_instruments');
            if (! $this->applyFileNumberFilterForTable($recordQuery, 'registered_instruments', ['file_number', 'file_no', 'fileno', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo'], $variants, 'collect.registered_instruments')) {
                \Log::debug('Caveat: Skipping registered_instruments search due to missing columns', ['table' => 'registered_instruments']);
            }
            $record = $recordQuery
                ->orderByDesc('instrumentDate')
                ->orderByDesc('id')
                ->first();

            if ($record) {
                $found = true;
                $raw = $this->convertRecordToArray($record);
                $sources['registered_instruments'] = [
                    'table' => 'registered_instruments',
                    'raw' => $raw,
                    'fields' => $this->normalizeRegisteredInstrument($raw),
                    'priority' => 80,
                ];
                \Log::info('Caveat: Found in registered_instruments', ['record_id' => $record->id ?? 'unknown']);
            }
        } catch (\Throwable $e) {
            \Log::warning('Caveat: Error searching registered_instruments', ['error' => $e->getMessage()]);
        }

        // CofO variations
        foreach (['CofO', 'cofo', 'CoFO', 'COFO'] as $tbl) {
            try {
                $tableExists = $conn->selectOne('SELECT 1 AS e FROM sys.tables WHERE name = ?', [$tbl]);
                if (! $tableExists) {
                    \Log::debug("Caveat: Table $tbl does not exist");
                    continue;
                }

                $recordQuery = $conn->table($tbl);
                $applied = $this->applyFileNumberFilterForTable($recordQuery, $tbl, ['file_no', 'FileNo', 'NewFileNo', 'mlsFNo', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo', 'NewKANGISFileno', 'np_fileno', 'cofo_no'], $variants, 'collect.cofO');
                \Log::info("Caveat: Searching in table $tbl", [
                    'columns_requested' => ['file_no', 'FileNo', 'NewFileNo', 'mlsFNo', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo', 'NewKANGISFileno', 'np_fileno', 'cofo_no'],
                    'columns_applied' => $applied ? $this->filterAvailableColumns($tbl, ['file_no', 'FileNo', 'NewFileNo', 'mlsFNo', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo', 'NewKANGISFileno', 'np_fileno', 'cofo_no']) : [],
                ]);

                if (! $applied) {
                    continue;
                }
                $record = $recordQuery
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->first();

                if ($record) {
                    $found = true;
                    $raw = $this->convertRecordToArray($record);
                    $sources['cofo'] = [
                        'table' => $tbl,
                        'raw' => $raw,
                        'fields' => $this->normalizeCofORecord($raw),
                        'priority' => 70,
                    ];
                    \Log::info("Caveat: Found in $tbl", ['record_id' => $record->id ?? 'unknown']);
                    break; // prefer first matching CofO variant
                } else {
                    \Log::info("Caveat: No records found in $tbl with given variants");
                }
            } catch (\Throwable $e) {
                \Log::warning("Caveat: Error searching $tbl", ['error' => $e->getMessage()]);
            }
        }

        // Optional fallback: dbo.fileNumber for metadata (does not satisfy business rule)
        try {
            $recordQuery = $conn->table('fileNumber');
            $this->applyFileNumberFilter(
                $recordQuery,
                ['mlsfNo', 'kangisFileNo', 'NewKANGISFileNo', 'st_file_no'],
                $variants
            );
            $record = $recordQuery
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->first();

            if ($record) {
                $raw = $this->convertRecordToArray($record);
                $sources['file_number'] = [
                    'table' => 'fileNumber',
                    'raw' => $raw,
                    'fields' => $this->normalizeFileNumberRecord($raw),
                    'priority' => 40,
                ];
            }
        } catch (\Throwable $e) {
            // ignore fallback failure
        }

        // Determine primary source by highest priority
        $primarySource = null;
        if (! empty($sources)) {
            $sorted = collect($sources)
                ->sortByDesc(fn ($payload) => $payload['priority'] ?? 0)
                ->keys()
                ->all();
            $primarySource = $sorted[0] ?? null;
            // Reorder sources by priority for deterministic JSON
            $sources = collect($sources)
                ->sortByDesc(fn ($payload) => $payload['priority'] ?? 0)
                ->map(function ($payload, $key) {
                    $payload['source_key'] = $key;
                    return $payload;
                })
                ->all();
        }

        return [
            'found' => $found,
            'primary_source' => $primarySource,
            'sources' => $sources,
            'normalized' => $this->mergeNormalizedFields($sources),
        ];
    }

    protected function convertRecordToArray($record): array
    {
        if (is_array($record)) {
            return $record;
        }

        return json_decode(json_encode($record), true) ?? [];
    }

    protected function normalizePropertyRecord(array $record): array
    {
        $fields = [];

        $transactionType = $this->cleanString(Arr::get($record, 'transaction_type'));
        $instrumentType = $this->cleanString(Arr::get($record, 'instrument_type'));

        if ($encumbrance = $this->mapEncumbranceType($transactionType, $instrumentType)) {
            $fields['encumbrance_type'] = $this->makeFieldEntry($encumbrance, 'transaction_type', 0.92);
        }

        if ($instrumentType) {
            $fields['instrument_type'] = $this->makeFieldEntry($instrumentType, 'instrument_type', 0.9);
        } elseif ($transactionType) {
            $fields['instrument_type'] = $this->makeFieldEntry($transactionType, 'transaction_type', 0.75);
        }

        $fields['location'] = $this->makeFieldEntry(
            $this->firstNonEmpty([
                Arr::get($record, 'property_description'),
                Arr::get($record, 'location'),
                $this->combineParts([Arr::get($record, 'layout'), Arr::get($record, 'lgsaOrCity')])
            ]),
            'property_description',
            0.85
        );

        $partyFields = [
            'Assignor' => 'assignor',
            'Assignee' => 'assignee',
            'Mortgagor' => 'mortgagor',
            'Mortgagee' => 'mortgagee',
            'Surrenderor' => 'surrenderor',
            'Surrenderee' => 'surrenderee',
            'Lessor' => 'lessor',
            'Lessee' => 'lessee',
            'Grantor' => 'grantor',
            'Grantee' => 'grantee',
        ];

        $parties = [];
        foreach ($partyFields as $column => $alias) {
            $value = $this->cleanString(Arr::get($record, $column));
            if ($value) {
                $parties[$alias] = $this->makeFieldEntry($value, $column, 0.88);
            }
        }

        if (! empty($parties['grantor'])) {
            $fields['grantor'] = $parties['grantor'];
        }
        if (! empty($parties['grantee'])) {
            $fields['grantee'] = $parties['grantee'];
        }

        // Heuristic for petitioner: prefer Assignor/Lessor/Mortgagor/Grantor depending on transaction type
        if ($petitioner = $this->determinePetitioner($transactionType, $parties)) {
            $fields['petitioner'] = $petitioner;
        }

        $serial = $this->cleanString(Arr::get($record, 'serialNo'));
        $page = $this->cleanString(Arr::get($record, 'pageNo'));
        $volume = $this->cleanString(Arr::get($record, 'volumeNo'));

        $fields['serial_no'] = $this->makeFieldEntry($serial, 'serialNo', 0.9);
        $fields['page_no'] = $this->makeFieldEntry($page ?: $serial, $page ? 'pageNo' : 'serialNo', $page ? 0.85 : 0.7);
        $fields['volume_no'] = $this->makeFieldEntry($volume, 'volumeNo', 0.9);
        $fields['registration_number'] = $this->makeFieldEntry(
            $this->composeRegistrationNumber($serial, $page ?: $serial, $volume),
            'serialNo/pageNo/volumeNo',
            0.92
        );

        $fields['start_date'] = $this->makeFieldEntry(
            $this->formatDateTimeForInput(Arr::get($record, 'transaction_date') ?: Arr::get($record, 'regDate')),
            Arr::get($record, 'transaction_date') ? 'transaction_date' : 'regDate',
            0.75
        );

        $fields['instructions'] = $this->makeFieldEntry($this->cleanString(Arr::get($record, 'schedule')), 'schedule', 0.55);
        $fields['remarks'] = $this->makeFieldEntry($this->cleanString(Arr::get($record, 'caveated_comment')), 'caveated_comment', 0.5);

        $fields['land_use'] = $this->makeFieldEntry($this->cleanString(Arr::get($record, 'land_use')), 'land_use', 0.4);

        return array_filter($fields);
    }

    protected function normalizeRegisteredInstrument(array $record): array
    {
        $fields = [];

        $instrumentType = $this->cleanString(Arr::get($record, 'instrument_type'));
        if ($instrumentType) {
            $fields['instrument_type'] = $this->makeFieldEntry($instrumentType, 'instrument_type', 0.82);
        }

        if ($encumbrance = $this->mapEncumbranceType($instrumentType, null)) {
            $fields['encumbrance_type'] = $this->makeFieldEntry($encumbrance, 'instrument_type', 0.8);
        }

        $fields['location'] = $this->makeFieldEntry(
            $this->firstNonEmpty([
                Arr::get($record, 'location'),
                $this->combineParts([Arr::get($record, 'plotNumber'), Arr::get($record, 'size')])
            ]),
            'location',
            0.68
        );

        foreach (['Grantor' => 'grantor', 'Grantee' => 'grantee'] as $column => $key) {
            $value = $this->cleanString(Arr::get($record, $column));
            if ($value) {
                $fields[$key] = $this->makeFieldEntry($value, $column, 0.78);
            }
        }

        $fields['petitioner'] = $fields['grantor'] ?? null;

        $serial = $this->cleanString(Arr::get($record, 'reg_serial') ?: Arr::get($record, 'serialNo'));
        $page = $this->cleanString(Arr::get($record, 'reg_page') ?: Arr::get($record, 'pageNo'));
        $volume = $this->cleanString(Arr::get($record, 'reg_volume') ?: Arr::get($record, 'volumeNo'));

        $fields['serial_no'] = $this->makeFieldEntry($serial, 'reg_serial', 0.7);
        $fields['page_no'] = $this->makeFieldEntry($page ?: $serial, $page ? 'reg_page' : 'reg_serial', $page ? 0.65 : 0.55);
        $fields['volume_no'] = $this->makeFieldEntry($volume, 'reg_volume', 0.7);
        $fields['registration_number'] = $this->makeFieldEntry(
            $this->composeRegistrationNumber($serial, $page ?: $serial, $volume),
            'reg_serial/reg_page/reg_volume',
            0.72
        );

        $fields['start_date'] = $this->makeFieldEntry(
            $this->formatDateTimeForInput(Arr::get($record, 'instrumentDate')),
            'instrumentDate',
            0.65
        );

        $fields['instructions'] = $this->makeFieldEntry($this->cleanString(Arr::get($record, 'comments')), 'comments', 0.5);
        $fields['remarks'] = $this->makeFieldEntry($this->cleanString(Arr::get($record, 'remarks')), 'remarks', 0.5);

        return array_filter($fields);
    }

    protected function normalizeCofORecord(array $record): array
    {
        $fields = [];

        $fields['instrument_type'] = $this->makeFieldEntry(
            $this->cleanString(Arr::get($record, 'instrument_type') ?: Arr::get($record, 'cofo_type') ?: Arr::get($record, 'transaction_type')),
            Arr::exists($record, 'instrument_type') ? 'instrument_type' : (Arr::exists($record, 'cofo_type') ? 'cofo_type' : 'transaction_type'),
            0.6
        );

        $fields['encumbrance_type'] = $this->makeFieldEntry('Government Acquisition/Reservation', 'cofo', 0.4);

        $fields['location'] = $this->makeFieldEntry(
            $this->firstNonEmpty([
                Arr::get($record, 'location'),
                Arr::get($record, 'plot_no'),
                Arr::get($record, 'property_description'),
                $this->combineParts([Arr::get($record, 'layout'), Arr::get($record, 'lgsaOrCity')])
            ]),
            'location',
            0.55
        );

        foreach (['Grantor' => 'grantor', 'Grantee' => 'grantee'] as $column => $key) {
            $value = $this->cleanString(Arr::get($record, $column));
            if ($value) {
                $fields[$key] = $this->makeFieldEntry($value, $column, 0.62);
            }
        }

        $fields['petitioner'] = $fields['grantor'] ?? null;

        $serial = $this->cleanString(Arr::get($record, 'serialNo'));
        $page = $this->cleanString(Arr::get($record, 'pageNo'));
        $volume = $this->cleanString(Arr::get($record, 'volumeNo'));

        $fields['serial_no'] = $this->makeFieldEntry($serial, 'serialNo', 0.6);
        $fields['page_no'] = $this->makeFieldEntry($page ?: $serial, $page ? 'pageNo' : 'serialNo', $page ? 0.55 : 0.5);
        $fields['volume_no'] = $this->makeFieldEntry($volume, 'volumeNo', 0.6);
        $fields['registration_number'] = $this->makeFieldEntry(
            $this->composeRegistrationNumber($serial, $page ?: $serial, $volume),
            'serialNo/pageNo/volumeNo',
            0.62
        );

        $fields['start_date'] = $this->makeFieldEntry(
            $this->formatDateTimeForInput(Arr::get($record, 'transaction_date') ?: Arr::get($record, 'transactionDate')),
            Arr::get($record, 'transaction_date') ? 'transaction_date' : 'transactionDate',
            0.55
        );

        $fields['remarks'] = $this->makeFieldEntry($this->cleanString(Arr::get($record, 'remarks') ?: Arr::get($record, 'caveated_comment')), 'remarks', 0.5);

        $fields['land_use'] = $this->makeFieldEntry($this->cleanString(Arr::get($record, 'land_use')), 'land_use', 0.5);

        return array_filter($fields);
    }

    protected function normalizeFileNumberRecord(array $record): array
    {
        $fields = [];

        $fields['location'] = $this->makeFieldEntry(
            $this->firstNonEmpty([
                Arr::get($record, 'location'),
                Arr::get($record, 'plot_no'),
            ]),
            'location',
            0.35
        );

        $fields['grantor'] = $this->makeFieldEntry($this->cleanString(Arr::get($record, 'FileName')), 'FileName', 0.3);

        $fields['petitioner'] = $fields['grantor'] ?? null;

        return array_filter($fields);
    }

    protected function mergeNormalizedFields(array $sources): array
    {
        $merged = [];

        foreach ($sources as $sourceKey => $payload) {
            $priority = $payload['priority'] ?? 0;
            $tableName = $payload['table'] ?? $sourceKey;

            foreach ($payload['fields'] as $field => $entry) {
                if (! is_array($entry) || ! isset($entry['value']) || $entry['value'] === null || $entry['value'] === '') {
                    continue;
                }

                $candidate = array_merge($entry, [
                    'source' => $tableName,
                    'source_key' => $sourceKey,
                    'priority' => $priority,
                ]);

                if (! isset($merged[$field])) {
                    $merged[$field] = $candidate;
                    continue;
                }

                if ($this->shouldReplaceField($merged[$field], $candidate)) {
                    $candidate['alternatives'] = isset($merged[$field]) ? [$merged[$field]] : [];
                    $merged[$field] = $candidate;
                } else {
                    $merged[$field]['alternatives'] = $merged[$field]['alternatives'] ?? [];
                    $merged[$field]['alternatives'][] = $candidate;
                }
            }
        }

        return [
            'fields' => $merged,
            'source_order' => array_values(array_map(fn ($payload) => $payload['table'] ?? $payload['source_key'], $sources)),
        ];
    }

    /**
     * Retrieve a table's columns (cached) using INFORMATION_SCHEMA for SQL Server.
     */
    protected function getTableColumns(string $table): array
    {
        $cacheKey = strtolower($table);
        if (isset($this->tableColumnCache[$cacheKey])) {
            return $this->tableColumnCache[$cacheKey];
        }

        try {
            $rows = DB::connection('sqlsrv')->select(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE LOWER(TABLE_NAME) = LOWER(?)",
                [$table]
            );

            $columns = array_map(static function ($row) {
                return isset($row->COLUMN_NAME) ? $row->COLUMN_NAME : (isset($row['COLUMN_NAME']) ? $row['COLUMN_NAME'] : null);
            }, $rows);

            $columns = array_values(array_filter($columns, static fn ($value) => $value !== null && $value !== ''));
            return $this->tableColumnCache[$cacheKey] = $columns;
        } catch (\Throwable $e) {
            \Log::warning('Caveat: Failed to fetch table columns', [
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
            return $this->tableColumnCache[$cacheKey] = [];
        }
    }

    /**
     * Filter candidate columns against actual table columns (case-insensitive).
     */
    protected function filterAvailableColumns(string $table, array $candidates): array
    {
        $tableColumns = $this->getTableColumns($table);
        if (empty($tableColumns)) {
            return [];
        }

        $lookup = [];
        foreach ($tableColumns as $column) {
            $lookup[strtolower($column)] = $column;
        }

        $matched = [];
        foreach ($candidates as $candidate) {
            $key = strtolower($candidate);
            if (isset($lookup[$key])) {
                $matched[] = $lookup[$key];
            }
        }

        return array_values(array_unique($matched));
    }

    /**
     * Apply file-number variants to a query only when the table exposes matching columns.
     * Returns true when the filter was applied, false otherwise.
     */
    protected function applyFileNumberFilterForTable($query, string $table, array $candidates, array $variants, ?string $context = null): bool
    {
        $columns = $this->filterAvailableColumns($table, $candidates);
        if (empty($columns)) {
            if ($context) {
                \Log::debug('Caveat: No matching file-number columns for table', [
                    'table' => $table,
                    'context' => $context,
                    'requested_columns' => $candidates,
                ]);
            }
            return false;
        }

        $this->applyFileNumberFilter($query, $columns, $variants);
        return true;
    }

    protected function shouldReplaceField(array $current, array $candidate): bool
    {
        $currentConfidence = $current['confidence'] ?? 0;
        $candidateConfidence = $candidate['confidence'] ?? 0;

        if ($candidateConfidence > $currentConfidence + 0.05) {
            return true;
        }

        if (abs($candidateConfidence - $currentConfidence) <= 0.05) {
            return ($candidate['priority'] ?? 0) > ($current['priority'] ?? 0);
        }

        return false;
    }

    protected function makeFieldEntry($value, string $sourceField, float $confidence): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === 'N/A' || $value === '-') {
            return null;
        }

        return [
            'value' => $value,
            'source_field' => $sourceField,
            'confidence' => round($confidence, 2),
        ];
    }

    protected function firstNonEmpty(array $values)
    {
        foreach ($values as $value) {
            $clean = $this->cleanString($value);
            if ($clean !== null && $clean !== '') {
                return $clean;
            }
        }

        return null;
    }

    protected function combineParts(array $parts): ?string
    {
        $filtered = array_filter(array_map([$this, 'cleanString'], $parts));
        if (empty($filtered)) {
            return null;
        }

        return implode(', ', $filtered);
    }

    /**
     * Generate common variants for a given file number, accounting for case, spacing,
     * delimiter swaps, and leading zero padding on the serial segment. This improves
     * lookup robustness across legacy tables that store values with differing formats.
     */
    protected function buildFileNumberVariants(string $fileNumber): array
    {
        $trimmed = trim($fileNumber);
        if ($trimmed === '') {
            return [];
        }

        $variants = [$trimmed];

        $upper = strtoupper($trimmed);
        $lower = strtolower($trimmed);

        $variants[] = $upper;
        $variants[] = $lower;

        $collapsed = preg_replace('/\s+/', '', $trimmed);
        if ($collapsed && $collapsed !== $trimmed) {
            $variants[] = $collapsed;
        }

        $collapsedUpper = preg_replace('/\s+/', '', $upper);
        if ($collapsedUpper && $collapsedUpper !== $upper) {
            $variants[] = $collapsedUpper;
        }

        $collapsedLower = preg_replace('/\s+/', '', $lower);
        if ($collapsedLower && $collapsedLower !== $lower) {
            $variants[] = $collapsedLower;
        }

        $dashToSlash = str_replace('-', '/', $upper);
        if ($dashToSlash !== $upper) {
            $variants[] = $dashToSlash;
        }

        $slashToDash = str_replace('/', '-', $upper);
        if ($slashToDash !== $upper) {
            $variants[] = $slashToDash;
        }

        $segments = preg_split('/[-\/]/', $upper);
        if ($segments !== false && count($segments) >= 2) {
            $serialSegment = array_pop($segments);
            $serialDigits = preg_replace('/\D+/', '', $serialSegment ?? '');

            if ($serialDigits !== '') {
                $serialBase = ltrim($serialDigits, '0');
                if ($serialBase === '') {
                    $serialBase = '0';
                }

                $baseLengths = [
                    strlen($serialSegment ?? ''),
                    strlen($serialDigits),
                    2,
                    3,
                    4,
                    5,
                ];

                $dynamicMaxLength = max(6, ($baseLengths[0] ?? 0) + 4, ($baseLengths[1] ?? 0) + 4);
                $dynamicRange = range(2, $dynamicMaxLength);

                $lengthCandidates = array_filter(array_unique(array_merge($baseLengths, $dynamicRange)));

                $prefixHyphen = implode('-', $segments);
                $prefixSlash = implode('/', $segments);

                $delimiters = [];
                if ($prefixHyphen !== '') {
                    $delimiters['-'] = $prefixHyphen;
                }
                if ($prefixSlash !== '' && $prefixSlash !== $prefixHyphen) {
                    $delimiters['/'] = $prefixSlash;
                }

                foreach ($delimiters as $delimiter => $prefix) {
                    $variants[] = $prefix . $delimiter . $serialBase;
                    foreach ($lengthCandidates as $length) {
                        $variants[] = $prefix . $delimiter . str_pad($serialBase, $length, '0', STR_PAD_LEFT);
                    }
                }
            }
        }

        // Normalize variants by adding uppercase counterparts, then filter duplicates/empties.
        $variants = array_merge($variants, array_map('strtoupper', $variants));

        return array_values(array_unique(array_filter($variants, fn ($value) => $value !== null && $value !== '')));
    }

    /**
     * Apply the generated file number variants to a query builder across the supplied columns.
     * Supports both the base query builder and Eloquent builder instances.
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param array $columns Columns to compare against the variants
     * @param array $variants File number variants generated by buildFileNumberVariants
     * @return mixed
     */
    protected function applyFileNumberFilter($query, array $columns, array $variants)
    {
        $variants = array_values(array_filter(array_unique($variants)));
        if (empty($columns) || empty($variants)) {
            return $query;
        }

        $query->where(function ($q) use ($columns, $variants) {
            $isFirstCondition = true;
            foreach ($columns as $column) {
                foreach ($variants as $variant) {
                    if ($isFirstCondition) {
                        $q->where($column, $variant);
                        $isFirstCondition = false;
                    } else {
                        $q->orWhere($column, $variant);
                    }
                }
            }
        });

        return $query;
    }

    protected function cleanString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : $value;

        if ($value === '' || $value === 'N/A' || $value === '-') {
            return null;
        }

        return (string) $value;
    }

    protected function composeRegistrationNumber(?string $serial, ?string $page, ?string $volume): ?string
    {
        if (! $serial || ! $volume) {
            return null;
        }

        $page = $page ?: $serial;

        return sprintf('%s/%s/%s', $serial, $page, $volume);
    }

    protected function formatDateTimeForInput($value): ?string
    {
        $value = $this->cleanString($value);
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function mapEncumbranceType(?string $primary, ?string $secondary): ?string
    {
        $candidates = collect([$primary, $secondary])
            ->filter()
            ->map(fn ($value) => strtolower($value))
            ->all();

        $map = [
            'mortgage' => 'Mortgage',
            'charge' => 'Charge',
            'lien' => 'Lien',
            'assignment' => 'Deed of Assignment/Transfer Not Completed',
            'transfer' => 'Deed of Assignment/Transfer Not Completed',
            'leasehold' => 'Leasehold Interest',
            'lease' => 'Leasehold Interest',
            'sub-lease' => 'Sub-Lease/Sub-Under Lease',
            'sublease' => 'Sub-Lease/Sub-Under Lease',
            'power of attorney' => 'Power of Attorney',
            'probate' => 'Probate/Letters of Administration',
            'court' => 'Court Order/Restraining Order',
            'litigation' => 'Pending Litigation (Lis Pendens)',
            'caution' => 'Caution (General or Specific)',
            'government' => 'Government Acquisition/Reservation',
            'reservation' => 'Government Acquisition/Reservation',
        ];

        foreach ($candidates as $candidate) {
            foreach ($map as $keyword => $target) {
                if (str_contains($candidate, $keyword)) {
                    return $target;
                }
            }
        }

        return null;
    }

    protected function determinePetitioner(?string $transactionType, array $parties): ?array
    {
        $transactionType = strtolower($transactionType ?? '');

        $order = [
            'mortgage' => ['mortgagor', 'mortgagee', 'grantor'],
            'assignment' => ['assignor', 'grantor'],
            'transfer' => ['assignor', 'grantor'],
            'lease' => ['lessor', 'grantor'],
            'surrender' => ['surrenderor', 'grantor'],
            'default' => ['grantor', 'assignor', 'lessor'],
        ];

        $keys = collect($order)
            ->filter(function ($_, $key) use ($transactionType) {
                return $key !== 'default' && str_contains($transactionType, $key);
            })
            ->first() ?? $order['default'];

        foreach ($keys as $key) {
            if (! empty($parties[$key])) {
                return $parties[$key];
            }
        }

        return null;
    }

    /**
     * Check for potential duplicate caveats before form submission
     * This endpoint allows the frontend to warn users about potential duplicates
     */
    public function checkDuplicates(Request $request)
    {
        $validated = $request->validate([
            'file_number' => 'required|string|max:100',
            'encumbrance_type' => 'nullable|string|max:100',
            'petitioner' => 'nullable|string|max:255',
        ]);

        $fileNumber = $validated['file_number'];
        
        // Find all existing caveats for this file number (including all statuses for informational purposes)
        $allExistingCaveats = $this->findExistingCaveats($fileNumber, ['active', 'draft', 'released', 'lifted']);
        $activeExistingCaveats = $this->findExistingCaveats($fileNumber, ['active', 'draft']);
        
        $warnings = [];
        $blockingIssues = [];
        
        if ($activeExistingCaveats->isNotEmpty()) {
            // Check for exact duplicates if we have encumbrance_type and petitioner
            if (!empty($validated['encumbrance_type']) && !empty($validated['petitioner'])) {
                $exactDuplicate = $activeExistingCaveats->where('encumbrance_type', $validated['encumbrance_type'])
                                                       ->where('petitioner', $validated['petitioner'])
                                                       ->first();
                
                if ($exactDuplicate) {
                    $blockingIssues[] = [
                        'type' => 'exact_duplicate',
                        'message' => 'Exact duplicate caveat exists',
                        'caveat' => $exactDuplicate,
                        'severity' => 'error'
                    ];
                }
            }
            
            // Check for same encumbrance type
            if (!empty($validated['encumbrance_type'])) {
                $sameEncumbrance = $activeExistingCaveats->where('encumbrance_type', $validated['encumbrance_type'])->first();
                if ($sameEncumbrance && empty($blockingIssues)) {
                    $blockingIssues[] = [
                        'type' => 'same_encumbrance',
                        'message' => 'Caveat with same encumbrance type exists',
                        'caveat' => $sameEncumbrance,
                        'severity' => 'error'
                    ];
                }
            }
            
            // Check for same petitioner
            if (!empty($validated['petitioner'])) {
                $samePetitioner = $activeExistingCaveats->where('petitioner', $validated['petitioner'])->first();
                if ($samePetitioner && empty($blockingIssues)) {
                    $blockingIssues[] = [
                        'type' => 'same_petitioner',
                        'message' => 'You already have an active caveat on this file',
                        'caveat' => $samePetitioner,
                        'severity' => 'error'
                    ];
                }
            }
            
            // General warning about existing active caveats
            if (empty($blockingIssues)) {
                $warnings[] = [
                    'type' => 'general',
                    'message' => 'This file number has existing active caveats',
                    'caveats' => $activeExistingCaveats,
                    'severity' => 'warning'
                ];
            }
        }
        
        // Show historical caveats for reference
        $historicalCaveats = $allExistingCaveats->whereIn('status', ['released', 'lifted']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'file_number' => $fileNumber,
                'has_active_caveats' => $activeExistingCaveats->isNotEmpty(),
                'active_caveats_count' => $activeExistingCaveats->count(),
                'total_caveats_count' => $allExistingCaveats->count(),
                'blocking_issues' => $blockingIssues,
                'warnings' => $warnings,
                'active_caveats' => $activeExistingCaveats,
                'historical_caveats' => $historicalCaveats,
                'can_proceed' => empty($blockingIssues)
            ]
        ]);
    }

    /**
     * Create a new property record
     */
    public function createPropertyRecord(Request $request)
    {
        $validated = $request->validate([
            'file_number' => 'required|string|max:100',
            'location' => 'nullable|string|max:255',
            'grantor' => 'nullable|string|max:255',
            'grantee' => 'nullable|string|max:255',
            'instrument_type' => 'nullable|string|max:100',
            'transaction_date' => 'nullable|date',
            'property_description' => 'nullable|string|max:500'
        ]);

        try {
            $conn = DB::connection('sqlsrv');
            
            // Determine file number type and set appropriate field
            $fileNumberFields = $this->getFileNumberFields($validated['file_number']);
            
            // Insert into property_records
            $recordId = $conn->table('property_records')->insertGetId([
                'mlsFNo' => $fileNumberFields['mlsf'] ?: null,
                'kangisFileNo' => $fileNumberFields['kangis'] ?: null,
                'NewKANGISFileno' => $fileNumberFields['new_kangis'] ?: null,
                'property_description' => $validated['location'] ?: $validated['property_description'],
                'Grantor' => $validated['grantor'],
                'Grantee' => $validated['grantee'],
                'transaction_type' => $validated['instrument_type'],
                'transaction_date' => $validated['transaction_date'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Get the created record
            $record = $conn->table('property_records')->find($recordId);

            return response()->json([
                'success' => true,
                'data' => $record,
                'message' => 'Property record created successfully'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create property record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file number fields based on pattern
     */
    private function getFileNumberFields(string $fileNumber): array
    {
        $fields = [
            'mlsf' => null,
            'kangis' => null,
            'new_kangis' => null
        ];

        if (preg_match('/^RES-\d{4}-\d+/', $fileNumber)) {
            // MLSF file number (format: RES-YYYY-NNNN)
            $fields['mlsf'] = $fileNumber;
        } elseif (preg_match('/^NK\d+/', $fileNumber)) {
            // New KANGIS file number (starts with NK)
            $fields['new_kangis'] = $fileNumber;
        } elseif (preg_match('/^KN\d+/', $fileNumber)) {
            // KANGIS file number (starts with KN)
            $fields['kangis'] = $fileNumber;
        } else {
            // Default to KANGIS if pattern doesn't match
            $fields['kangis'] = $fileNumber;
        }

        return $fields;
    }

    /**
     * Check for duplicate caveat placement
     * Check if the file number already exists in any of the caveat table fields:
     * - file_number_kangis
     * - file_number_mlsf  
     * - file_number_new_kangis
     * If any exist (regardless of status), then the caveat is duplicate - don't save it
     */
    private function checkDuplicateCaveat(string $fileNumber, array $validated): array
    {
        // Check for existing caveats with the same file number (ALL statuses)
        $existingCaveats = $this->findExistingCaveats($fileNumber, ['active', 'draft', 'released', 'lifted', 'expired']);
        
        if ($existingCaveats->isNotEmpty()) {
            $existingCaveat = $existingCaveats->first();
            
            return [
                'isDuplicate' => true,
                'message' => 'Duplicate caveat detected. This file number already exists in the caveat table. Existing caveat by: ' . $existingCaveat->petitioner . ' (' . $existingCaveat->encumbrance_type . ') with status: ' . $existingCaveat->status . '. Caveat No: ' . $existingCaveat->caveat_number,
                'existingCaveat' => $existingCaveat,
                'duplicateType' => 'file_number_exists'
            ];
        }
        
        return [
            'isDuplicate' => false,
            'message' => null,
            'existingCaveat' => null,
            'duplicateType' => null
        ];
    }

    /**
     * Find existing caveats for a file number
     * Check if file number exists in any of the caveat table fields:
     * - file_number_kangis
     * - file_number_mlsf  
     * - file_number_new_kangis
     */
    private function findExistingCaveats(string $fileNumber, array $statuses = ['active', 'draft'])
    {
        $query = Caveat::whereIn('status', $statuses);
        $variants = $this->buildFileNumberVariants($fileNumber);
        
        // Search across all file number fields in the caveat table
        $this->applyFileNumberFilter($query, ['file_number_kangis', 'file_number_mlsf', 'file_number_new_kangis'], $variants);
        
        return $query->get();
    }

    /**
     * Store file number in appropriate field based on type/pattern
     */
    private function setFileNumberFields(array &$validated, string $fileNumber): void
    {
        // Determine file number type based on pattern and store in appropriate field
        $fileNumberFields = $this->getFileNumberFields($fileNumber);
        
        // Set the specific file number fields
        $validated['file_number_kangis'] = $fileNumberFields['kangis'];
        $validated['file_number_mlsf'] = $fileNumberFields['mlsf'];
        $validated['file_number_new_kangis'] = $fileNumberFields['new_kangis'];
    }
}
