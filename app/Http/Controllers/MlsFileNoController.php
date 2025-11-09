<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MlsFileNoController extends Controller
{
    /**
     * Display the Manage MLS FileNo page
     */
    public function index()
    {
        try {
            // Test database connection first
            DB::connection('sqlsrv')->getPdo();
            Log::info('MLS FileNo page: Database connection successful');
            
            // Count all rows in the fileNumber table
            $totalCount = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->count();

            Log::info('MLS FileNo page: Total file numbers: ' . $totalCount);

            return view('mls_fileno.index', compact('totalCount'));

        } catch (\Exception $e) {
            Log::error('Error accessing MLS FileNo page', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('mls_fileno.index', [
                'totalCount' => 0,
                'mlsFileNumbers' => collect([]), // Empty collection
                'error' => 'Database Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        try {
            // Test database connection first
            DB::connection('sqlsrv')->getPdo();
            // Build a query builder and let Yajra/DataTables perform server-side paging, ordering and searching.
            $query = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->select([
                    'fileNumber.id',
                    'fileNumber.mlsfNo',
                    'fileNumber.FileName',
                    'fileNumber.created_at',
                    'fileNumber.updated_at',
                    'fileNumber.location',
                    'fileNumber.created_by',
                    'fileNumber.is_deleted',
                    'fileNumber.SOURCE',
                    'fileNumber.commissioning_date',
                    'fileNumber.kangisFileNo',
                    'fileNumber.NewKANGISFileNo',
                    'fileNumber.st_file_no'
                ])
                ->where(function($q) {
                    // Include records with MLS file numbers OR ST file numbers
                    $q->whereNotNull('fileNumber.mlsfNo')
                      ->orWhereNotNull('fileNumber.st_file_no')
                      ->orWhereNotNull('fileNumber.kangisFileNo')
                      ->orWhereNotNull('fileNumber.NewKANGISFileNo');
                })
                ->where(function($q) {
                    $q->whereNull('fileNumber.is_deleted')->orWhere('fileNumber.is_deleted', 0);
                });

            // Apply simple custom filters sent from DataTables AJAX
            if ($request->filled('year')) {
                // filter by created_at year
                $year = intval($request->get('year'));
                if ($year > 1900) {
                    $query->whereYear('fileNumber.created_at', $year);
                }
            }

            if ($request->filled('status')) {
                $status = trim($request->get('status'));
                if ($status !== '') {
                    $query->where('fileNumber.SOURCE', $status);
                }
            }

            // Allow server-side global search to be handled by DataTables when using query builder.

            return DataTables::of($query)
                ->orderColumn('commissioning_date', function ($query, $order) {
                    $query->orderBy('fileNumber.commissioning_date', $order);
                })
                ->orderColumn('created_by', function ($query, $order) {
                    $query->orderBy('fileNumber.created_by', $order);
                })
                ->orderColumn('FileName', function ($query, $order) {
                    $query->orderBy('fileNumber.FileName', $order);
                })
                ->orderColumn('mlsfNo', function ($query, $order) {
                    $query->orderBy('fileNumber.mlsfNo', $order);
                })
                ->editColumn('mlsfNo', function($row) {
                    $numbers = $this->splitCompositeNumbers($row->mlsfNo ?? null);
                    $numbers = $this->filterUniqueNumbers($numbers);

                    if (!count($numbers)) {
                        return '<span class="text-sm text-gray-400">N/A</span>';
                    }

                    $badges = [];
                    foreach ($numbers as $number) {
                        $badgeClass = $this->resolveBadgeClass($number);
                        $badges[] = '<span class="file-number-badge ' . $badgeClass . '" title="MLS File Number">' .
                                    htmlspecialchars($number) . '</span>';
                    }

                    return '<span class="other-number-group">' . implode('', $badges) . '</span>';
                })
                ->editColumn('FileName', function($row) {
                    $fileName = $row->FileName ?? 'N/A';
                    return '<div class="max-w-xs truncate text-gray-900 font-medium" title="' . htmlspecialchars($fileName) . '">' . 
                           $fileName . '</div>';
                })
                ->editColumn('SOURCE', function($row) {
                    $source = $row->SOURCE;
                    
                    // Handle NULL or empty sources
                    if (empty($source) || is_null($source)) {
                        $source = 'Unknown';
                    }
                    
                    // Dynamic badge class assignment based on source value
                    $badgeClasses = [
                        'generated' => 'bg-green-100 text-green-800',
                        'captured' => 'bg-blue-100 text-blue-800', 
                        'migrated' => 'bg-purple-100 text-purple-800',
                        'imported' => 'bg-orange-100 text-orange-800',
                        'indexing' => 'bg-purple-100 text-purple-800',
                        'system' => 'bg-indigo-100 text-indigo-800',
                        'manual' => 'bg-yellow-100 text-yellow-800',
                        'bulk' => 'bg-pink-100 text-pink-800',
                        'api' => 'bg-teal-100 text-teal-800',
                        'upload' => 'bg-cyan-100 text-cyan-800',
                        'legacy' => 'bg-amber-100 text-amber-800',
                        'unknown' => 'bg-gray-100 text-gray-800'
                    ];
                    
                    $sourceKey = strtolower(trim($source));
                    $badgeClass = $badgeClasses[$sourceKey] ?? 'bg-gray-100 text-gray-800';
                    
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . 
                           $badgeClass . '" title="Source: ' . htmlspecialchars($source) . '">' . 
                           ucfirst($source) . '</span>';
                })
                ->editColumn('created_at', function($row) {
                    return $row->created_at ? 
                           '<span class="text-sm text-gray-900 font-medium">' . 
                           \Carbon\Carbon::parse($row->created_at)->format('M d, Y') . 
                           '</span><br><span class="text-xs text-gray-500">' .
                           \Carbon\Carbon::parse($row->created_at)->format('H:i') . 
                           '</span>' : '<span class="text-sm text-gray-400">N/A</span>';
                })
                ->editColumn('updated_at', function($row) {
                    return $row->updated_at ? 
                           '<span class="text-sm text-gray-900 font-medium">' . 
                           \Carbon\Carbon::parse($row->updated_at)->format('M d, Y') . 
                           '</span><br><span class="text-xs text-gray-500">' .
                           \Carbon\Carbon::parse($row->updated_at)->format('H:i') . 
                           '</span>' : '<span class="text-sm text-gray-400">N/A</span>';
                })
                ->editColumn('commissioning_date', function($row) {
                    return $row->commissioning_date ? 
                           '<span class="text-sm text-gray-900 font-medium">' . 
                           \Carbon\Carbon::parse($row->commissioning_date)->format('M d, Y') . 
                           '</span>' : 
                           '<span class="text-sm text-gray-400">Not Set</span>';
                })
                ->editColumn('location', function($row) {
                    $location = $row->location ?? 'N/A';
                    return '<span class="text-sm text-gray-900 font-medium" title="' . htmlspecialchars($location) . '">' . 
                           $location . '</span>';
                })
                ->editColumn('created_by', function($row) {
                    $createdBy = $row->created_by ?? '-';
                    return '<span class="text-sm text-gray-900 font-medium">' . e($createdBy) . '</span>';
                })
                ->addColumn('OtherNumbers', function($row) {
                    $kangisNumbers = $this->filterUniqueNumbers(
                        $this->splitCompositeNumbers($row->kangisFileNo ?? null)
                    );

                    $newKangisNumbers = $this->filterUniqueNumbers(
                        $this->splitCompositeNumbers($row->NewKANGISFileNo ?? null)
                    );

                    // Do not de-duplicate ST numbers; show every entry exactly as stored
                    $stNumbers = $this->splitCompositeNumbers($row->st_file_no ?? null);

                    $parts = array_merge($kangisNumbers, $newKangisNumbers, $stNumbers);

                    if (!count($parts)) {
                        return '-';
                    }

                    $badges = [];
                    foreach ($parts as $number) {
                        $escaped = e($number);
                        $badgeClass = $this->resolveBadgeClass($number);
                        $badges[] = '<span class="file-number-badge other-number-badge ' . $badgeClass . '" title="Alternate file number">' . $escaped . '</span>';
                    }

                    return '<span class="other-number-group">' . implode('', $badges) . '</span>';
                })
                ->addColumn('actions', function($row) {
                    return '
                        <div class="flex items-center space-x-1">
                            <button onclick="viewDetails(' . $row->id . ')" 
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 hover:text-blue-800 transition-all duration-200 shadow-sm">
                                <i data-lucide="eye" class="w-3 h-3 mr-1"></i>
                                View
                            </button>
                            <button onclick="editRecord(' . $row->id . ')" 
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-100 rounded-md hover:bg-emerald-200 hover:text-emerald-800 transition-all duration-200 shadow-sm">
                                <i data-lucide="edit-3" class="w-3 h-3 mr-1"></i>
                                Edit
                            </button>
                        </div>';
                })
                ->rawColumns(['mlsfNo', 'FileName', 'SOURCE', 'created_at', 'updated_at', 'commissioning_date', 'location', 'created_by', 'actions', 'OtherNumbers'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('Error fetching MLS FileNo data', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_params' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data: ' . $e->getMessage(),
                'data' => [],
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'draw' => $request->input('draw', 1)
            ], 500);
        }
    }

    /**
     * Get details of a specific file number
     */
    public function show($identifier)
    {
        try {
            // Try to find by ID first, then by file number
            $fileNumber = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where(function($query) use ($identifier) {
                    $query->where('id', $identifier)
                          ->orWhere('mlsfNo', $identifier);
                })
                ->first();

            if (!$fileNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'File number not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $fileNumber
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching file number details', [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a file number record
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'FileName' => 'required|string|max:500',
                'location' => 'nullable|string|max:255',
                'commissioning_date' => 'nullable|date'
            ]);

            $updated = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where('id', $id)
                ->update([
                    'FileName' => $validatedData['FileName'],
                    'location' => $validatedData['location'] ?? null,
                    'commissioning_date' => $validatedData['commissioning_date'] ?? null,
                    'updated_at' => now(),
                    'updated_by' => Auth::user()->first_name . ' ' . Auth::user()->last_name
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'File number updated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No changes made or file number not found'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error updating file number', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating file number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics for the dashboard
     */
    public function getStats()
    {
        try {
            $stats = [
                'total' => DB::connection('sqlsrv')
                    ->table('fileNumber')
                    ->count(),
                    
                'by_source' => DB::connection('sqlsrv')
                    ->table('fileNumber')
                    ->where(function($q) {
                        // Include records with MLS file numbers OR ST file numbers
                        $q->whereNotNull('mlsfNo')
                          ->orWhereNotNull('st_file_no')
                          ->orWhereNotNull('kangisFileNo')
                          ->orWhereNotNull('NewKANGISFileNo');
                    })
                    ->where(function($q) {
                        $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                    })
                    ->selectRaw('SOURCE, COUNT(*) as count')
                    ->groupBy('SOURCE')
                    ->orderBy('SOURCE')
                    ->get(),
                    
                'recent' => DB::connection('sqlsrv')
                    ->table('fileNumber')
                    ->where(function($q) {
                        // Include records with MLS file numbers OR ST file numbers
                        $q->whereNotNull('mlsfNo')
                          ->orWhereNotNull('st_file_no')
                          ->orWhereNotNull('kangisFileNo')
                          ->orWhereNotNull('NewKANGISFileNo');
                    })
                    ->where(function($q) {
                        $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                    })
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching MLS FileNo statistics', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available sources for filtering
     */
    public function getSources()
    {
        try {
            $sources = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where(function($q) {
                    // Include records with MLS file numbers OR ST file numbers
                    $q->whereNotNull('mlsfNo')
                      ->orWhereNotNull('st_file_no')
                      ->orWhereNotNull('kangisFileNo')
                      ->orWhereNotNull('NewKANGISFileNo');
                })
                ->whereNotNull('SOURCE')
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->select('SOURCE')
                ->distinct()
                ->orderBy('SOURCE')
                ->pluck('SOURCE')
                ->filter() // Remove empty values
                ->values();

            Log::info('MLS FileNo sources loaded', ['sources' => $sources->toArray()]);

            return response()->json([
                'success' => true,
                'data' => $sources
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching MLS FileNo sources', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching sources: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Test method specifically for ST file numbers debugging
     */
    public function testST()
    {
        try {
            // Query all records with st_file_no
            $stRecords = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->whereNotNull('st_file_no')
                ->select('id', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo', 'st_file_no', 'SOURCE', 'is_deleted', 'FileName')
                ->get();
            
            $response = [
                'total_st_records' => $stRecords->count(),
                'st_records' => $stRecords->toArray(),
            ];
            
            // Test the controller's query logic
            $controllerRecords = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->select([
                    'fileNumber.id',
                    'fileNumber.mlsfNo',
                    'fileNumber.kangisFileNo', 
                    'fileNumber.NewKANGISFileNo',
                    'fileNumber.st_file_no',
                    'fileNumber.SOURCE',
                    'fileNumber.is_deleted'
                ])
                ->where(function($q) {
                    // Include records with MLS file numbers OR ST file numbers
                    $q->whereNotNull('fileNumber.mlsfNo')
                      ->orWhereNotNull('fileNumber.st_file_no')
                      ->orWhereNotNull('fileNumber.kangisFileNo')
                      ->orWhereNotNull('fileNumber.NewKANGISFileNo');
                })
                ->where(function($q) {
                    $q->whereNull('fileNumber.is_deleted')->orWhere('fileNumber.is_deleted', 0);
                })
                ->get();
                
            $response['controller_query_test'] = [
                'total_records' => $controllerRecords->count(),
                'st_file_no_records' => $controllerRecords->whereNotNull('st_file_no')->count(),
                'st_dept_records' => $controllerRecords->where('SOURCE', 'ST Dept')->count(),
                'sample_records' => $controllerRecords->take(10)->toArray()
            ];
            
            // Test specific ST file numbers
            $searchNumbers = [
                'ST-RES-2025-1',
                'ST-COM-2025-1-001',
                'ST-COM-2025-2-001', 
                'ST-COM-2025-3-001',
                'ST-COM-2025-4',
                'ST-MIXED-2025-1'
            ];
            
            $response['search_results'] = [];
            foreach ($searchNumbers as $stNumber) {
                $found = DB::connection('sqlsrv')
                    ->table('fileNumber')
                    ->where(function($query) use ($stNumber) {
                        $query->where('st_file_no', 'LIKE', "%{$stNumber}%")
                              ->orWhere('mlsfNo', 'LIKE', "%{$stNumber}%")
                              ->orWhere('kangisFileNo', 'LIKE', "%{$stNumber}%")
                              ->orWhere('NewKANGISFileNo', 'LIKE', "%{$stNumber}%");
                    })
                    ->select('id', 'mlsfNo', 'kangisFileNo', 'NewKANGISFileNo', 'st_file_no', 'SOURCE', 'is_deleted')
                    ->get();
                    
                $response['search_results'][$stNumber] = [
                    'found_count' => $found->count(),
                    'records' => $found->toArray()
                ];
            }
            
            return response()->json($response, 200, [], JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Debug method to check database connection and data
     */
    public function debug()
    {
        try {
            // Test connection
            $connection = DB::connection('sqlsrv')->getPdo();
            
            // Get sample data
            $sampleData = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where(function($q) {
                    // Include records with MLS file numbers OR ST file numbers
                    $q->whereNotNull('mlsfNo')
                      ->orWhereNotNull('st_file_no')
                      ->orWhereNotNull('kangisFileNo')
                      ->orWhereNotNull('NewKANGISFileNo');
                })
                ->limit(5)
                ->get();
                
            // Get sources
            $sources = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where(function($q) {
                    // Include records with MLS file numbers OR ST file numbers
                    $q->whereNotNull('mlsfNo')
                      ->orWhereNotNull('st_file_no')
                      ->orWhereNotNull('kangisFileNo')
                      ->orWhereNotNull('NewKANGISFileNo');
                })
                ->select('SOURCE')
                ->distinct()
                ->pluck('SOURCE');
                
            // Get total count
            $totalCount = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where(function($q) {
                    // Include records with MLS file numbers OR ST file numbers
                    $q->whereNotNull('mlsfNo')
                      ->orWhereNotNull('st_file_no')
                      ->orWhereNotNull('kangisFileNo')
                      ->orWhereNotNull('NewKANGISFileNo');
                })
                ->count();

            return response()->json([
                'success' => true,
                'connection' => 'OK',
                'total_count' => $totalCount,
                'sources' => $sources,
                'sample_data' => $sampleData,
                'database_name' => DB::connection('sqlsrv')->getDatabaseName()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Split composite values (comma/newline separated or JSON encoded) into individual numbers.
     */
    private function splitCompositeNumbers($value): array
    {
        if (is_null($value)) {
            return [];
        }

        if (is_array($value)) {
            return $this->normalizeNumberCollection($value);
        }

        $string = trim((string) $value);
        if ($string === '' || strtoupper($string) === 'N/A') {
            return [];
        }

        $decoded = json_decode($string, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->normalizeNumberCollection($decoded);
        }

        $normalized = str_replace(["\r\n", "\r", "\n"], ',', $string);
        $parts = preg_split('/\s*(?:,|;|\|)+\s*/', $normalized) ?: [];

        $results = [];
        foreach ($parts as $fragment) {
            $fragment = trim($fragment);
            if ($fragment === '' || strtoupper($fragment) === 'N/A') {
                continue;
            }
            $results[] = $fragment;
        }

        return $results ?: [$string];
    }

    /**
     * Flatten nested arrays into a flat list of cleaned string numbers.
     */
    private function normalizeNumberCollection($value): array
    {
        $results = [];

        $walker = function ($item) use (&$results, &$walker) {
            if (is_array($item)) {
                foreach ($item as $sub) {
                    $walker($sub);
                }
                return;
            }

            if (is_null($item)) {
                return;
            }

            $candidate = trim((string) $item);
            if ($candidate === '' || strtoupper($candidate) === 'N/A') {
                return;
            }

            $results[] = $candidate;
        };

        $walker($value);

        return $results;
    }

    /**
     * Remove duplicates and blank values while keeping original order.
     */
    private function filterUniqueNumbers(array $numbers): array
    {
        $filtered = [];
        foreach ($numbers as $number) {
            $candidate = trim((string) $number);
            if ($candidate === '' || strtoupper($candidate) === 'N/A') {
                continue;
            }

            if (!in_array($candidate, $filtered, true)) {
                $filtered[] = $candidate;
            }
        }

        return $filtered;
    }

    /**
     * Resolve badge class name based on file number prefix for consistent styling
     */
    private function resolveBadgeClass(?string $fileNumber): string
    {
        if (empty($fileNumber)) {
            return 'badge-default';
        }

        $normalized = strtoupper(trim($fileNumber));
        $prefix = substr($normalized, 0, 3);

        $map = [
            'COM' => 'badge-com',
            'RES' => 'badge-res',
            'CON' => 'badge-con',
            'IND' => 'badge-ind',
            'AGR' => 'badge-agr',
            'MIX' => 'badge-mix',
            'SPE' => 'badge-spe',
            'REC' => 'badge-rec',
            'EDU' => 'badge-edu',
            'REL' => 'badge-rel',
            'KNM' => 'badge-knm',
            'MLK' => 'badge-mlk',
            'MLS' => 'badge-mls',
        ];

        if (Str::startsWith($normalized, 'ST-')) {
            $parts = explode('-', $normalized);
            if (isset($parts[1])) {
                $stPrefix = substr(strtoupper($parts[1]), 0, 3);
                return $map[$stPrefix] ?? 'badge-st';
            }
            return 'badge-st';
        }

        return $map[$prefix] ?? 'badge-default';
    }
}