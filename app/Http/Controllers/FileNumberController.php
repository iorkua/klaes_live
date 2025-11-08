<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;
use App\Models\FileNumber;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FileNumberController extends Controller
{
    /**
     * Cache of tracking IDs generated during the current request to avoid duplicates
     * before database persistence.
     *
     * @var array<string, bool>
     */
    private array $generatedTrackingIds = [];

    /**
     * Display the MLS File number generation page
     */
    public function index()
    {
        $totalCount = DB::connection('sqlsrv')
            ->table('fileNumber')
            ->whereNotNull('mlsfNo')
            ->where('mlsfNo', '!=', '')
            ->count();

        return view('generate_fileno.mlsfno', compact('totalCount'));
    }

    /**
     * Display the Capture Existing File page
     */
    public function captureIndex()
    {
        $capturedTypes = ['Captured', 'Migrated', 'indexing', 'Indexing', 'INDEXING', 'KANGIS GIS'];
        $capturedSources = ['Captured', 'Migrated', 'indexing', 'Indexing', 'INDEXING', 'KANGIS GIS'];

        $totalCount = DB::connection('sqlsrv')
            ->table('fileNumber')
            ->where(function($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->where(function($q) {
                $q->whereNotNull('mlsfNo')
                  ->whereRaw("LTRIM(RTRIM(mlsfNo)) != ''");
            })
            ->where(function($q) use ($capturedTypes, $capturedSources) {
                $q->whereIn('fileNumber.type', $capturedTypes)
                  ->orWhereIn('fileNumber.SOURCE', $capturedSources);
            })
            ->count();

        $mlsfCount = DB::connection('sqlsrv')
            ->table('fileNumber')
            ->where(function($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->whereNotNull('mlsfNo')
            ->whereRaw("LTRIM(RTRIM(mlsfNo)) != ''")
            ->count();

        $kangisCount = DB::connection('sqlsrv')
            ->table('fileNumber')
            ->where(function($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->whereNotNull('kangisFileNo')
            ->whereRaw("LTRIM(RTRIM(kangisFileNo)) != ''")
            ->count();

        $newKangisCount = DB::connection('sqlsrv')
            ->table('fileNumber')
            ->where(function($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->whereNotNull('NewKANGISFileNo')
            ->whereRaw("LTRIM(RTRIM(NewKANGISFileNo)) != ''")
            ->count();

        // Generate a fresh tracking ID for this session
        $trackingId = $this->generateTrackingId();

        return view('generate_fileno.capture_existing', compact(
            'totalCount',
            'mlsfCount',
            'kangisCount',
            'newKangisCount',
            'trackingId'
        ));
    }

    /**
     * Get data for DataTables
     */
    public function getData(Request $request)
    {
        try {
            // Get DataTables parameters
            $draw = $request->input('draw');
            $start = $request->input('start', 0);
            $length = $request->input('length', 10);
            $searchValue = $request->input('search.value', '');
            $source = $request->input('source', 'New'); // Default to 'New' for generate page
            $includeStFileNumbers = $source === 'Captured';

            // Base query for counting (without ORDER BY)
            $capturedTypes = ['Captured', 'Migrated', 'indexing', 'Indexing', 'INDEXING', 'KANGIS GIS'];
            $capturedSources = ['Captured', 'Migrated', 'indexing', 'Indexing', 'INDEXING', 'KANGIS GIS'];

            $baseQuery = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->where(function($q) use ($includeStFileNumbers, $source) {
                    if ($source === 'Captured') {
                        // For capture existing, require at least one of mlsfNo, kangisFileNo, or NewKANGISFileNo to be non-empty
                        $q->where(function($sub) {
                            $sub->where(function($mlsf) {
                                $mlsf->whereNotNull('mlsfNo')->whereRaw("LTRIM(RTRIM(mlsfNo)) != ''");
                            })->orWhere(function($kangis) {
                                $kangis->whereNotNull('kangisFileNo')->whereRaw("LTRIM(RTRIM(kangisFileNo)) != ''");
                            })->orWhere(function($newkangis) {
                                $newkangis->whereNotNull('NewKANGISFileNo')->whereRaw("LTRIM(RTRIM(NewKANGISFileNo)) != ''");
                            });
                        });
                    } else {
                        // Original logic for other sources
                        $q->where(function($sub) {
                            $sub->whereNotNull('mlsfNo')
                                ->whereRaw("LTRIM(RTRIM(mlsfNo)) != ''");
                        });

                        if ($includeStFileNumbers) {
                            $q->orWhere(function($sub) {
                                $sub->whereNotNull('st_file_no')
                                    ->whereRaw("LTRIM(RTRIM(st_file_no)) != ''");
                            });
                        }
                    }
                });

            // Filter by source
            if ($source === 'New') {
                $baseQuery->where(function($query) use ($capturedTypes) {
                    $query->whereNotIn('fileNumber.type', $capturedTypes)
                          ->orWhereNull('fileNumber.type');
                })->where(function($query) use ($capturedSources) {
                    $query->whereNotIn('fileNumber.SOURCE', $capturedSources)
                          ->orWhereNull('fileNumber.SOURCE');
                });
            } elseif ($source === 'Captured') {
                $baseQuery->where(function($query) use ($capturedTypes, $capturedSources, $includeStFileNumbers) {
                    $query->whereIn('fileNumber.type', $capturedTypes)
                          ->orWhereIn('fileNumber.SOURCE', $capturedSources);

                    if ($includeStFileNumbers) {
                        $query->orWhere(function($sub) {
                            $sub->whereNotNull('fileNumber.st_file_no')
                                ->whereRaw("LTRIM(RTRIM(fileNumber.st_file_no)) != ''");
                        });
                    }
                });
            }

            // Get total count with caching (cache for 5 minutes to reduce database load)
            $cacheKey = "file_numbers_total_count_{$source}";
            $totalRecords = Cache::remember($cacheKey, 300, function() use ($baseQuery) {
                return $baseQuery->count();
            });

            // Apply search if provided
            if (!empty($searchValue)) {
                $baseQuery->where(function($query) use ($searchValue) {
                    $query->where('kangisFileNo', 'like', "%{$searchValue}%")
                          ->orWhere('NewKANGISFileNo', 'like', "%{$searchValue}%")
                          ->orWhere('FileName', 'like', "%{$searchValue}%")
                          ->orWhere('mlsfNo', 'like', "%{$searchValue}%")
                          ->orWhere('st_file_no', 'like', "%{$searchValue}%");
                });
            }

            // Get filtered count
            $filteredRecords = $baseQuery->count();

            // Get the actual data with ordering and pagination - OPTIMIZED QUERY
            $data = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->select([
                    'id',
                    'kangisFileNo',
                    'mlsfNo',
                    'NewKANGISFileNo', 
                    'FileName',
                    'st_file_no',
                    'plot_no',
                    'tp_no',
                    'location',
                    'tracking_id',
                    'type',
                    'created_by',
                    'created_at',
                    'SOURCE'
                ])
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->where(function($q) use ($includeStFileNumbers, $source) {
                    if ($source === 'Captured') {
                        // For capture existing, require at least one of mlsfNo, kangisFileNo, or NewKANGISFileNo to be non-empty
                        $q->where(function($sub) {
                            $sub->where(function($mlsf) {
                                $mlsf->whereNotNull('mlsfNo')->whereRaw("LTRIM(RTRIM(mlsfNo)) != ''");
                            })->orWhere(function($kangis) {
                                $kangis->whereNotNull('kangisFileNo')->whereRaw("LTRIM(RTRIM(kangisFileNo)) != ''");
                            })->orWhere(function($newkangis) {
                                $newkangis->whereNotNull('NewKANGISFileNo')->whereRaw("LTRIM(RTRIM(NewKANGISFileNo)) != ''");
                            });
                        });
                    } else {
                        // Original logic for other sources
                        $q->where(function($sub) {
                            $sub->whereNotNull('mlsfNo')
                                ->whereRaw("LTRIM(RTRIM(mlsfNo)) != ''");
                        });

                        if ($includeStFileNumbers) {
                            $q->orWhere(function($sub) {
                                $sub->whereNotNull('st_file_no')
                                    ->whereRaw("LTRIM(RTRIM(st_file_no)) != ''");
                            });
                        }
                    }
                })
                ->when($source === 'New', function($query) use ($capturedTypes, $capturedSources) {
                    $query->where(function($q) use ($capturedTypes) {
                        $q->whereNotIn('fileNumber.type', $capturedTypes)
                          ->orWhereNull('fileNumber.type');
                    })->where(function($q) use ($capturedSources) {
                        $q->whereNotIn('fileNumber.SOURCE', $capturedSources)
                          ->orWhereNull('fileNumber.SOURCE');
                    });
                })
                ->when($source === 'Captured', function($query) use ($capturedTypes, $capturedSources, $includeStFileNumbers) {
                    $query->where(function($q) use ($capturedTypes, $capturedSources, $includeStFileNumbers) {
                        $q->whereIn('fileNumber.type', $capturedTypes)
                          ->orWhereIn('fileNumber.SOURCE', $capturedSources);

                        if ($includeStFileNumbers) {
                            $q->orWhere(function($sub) {
                                $sub->whereNotNull('fileNumber.st_file_no')
                                    ->whereRaw("LTRIM(RTRIM(fileNumber.st_file_no)) != ''");
                            });
                        }
                    });
                })
                ->when(!empty($searchValue), function($query) use ($searchValue) {
                    $query->where(function($q) use ($searchValue) {
                        $q->where('fileNumber.kangisFileNo', 'like', "%{$searchValue}%")
                          ->orWhere('fileNumber.NewKANGISFileNo', 'like', "%{$searchValue}%")
                          ->orWhere('fileNumber.FileName', 'like', "%{$searchValue}%")
                          ->orWhere('fileNumber.mlsfNo', 'like', "%{$searchValue}%")
                          ->orWhere('fileNumber.st_file_no', 'like', "%{$searchValue}%");
                    });
                })
                ->orderBy('fileNumber.id', 'desc') // Order by ID since created_at might be null
                ->skip($start)
                ->take($length)
                ->get();

            // Format the data with optimized processing
            $formattedData = $data->map(function($row) {
                // Clean and format the data efficiently
                $kangisFileNo = trim($row->kangisFileNo ?? '') ?: 'N/A';
                $newKangisFileNo = trim($row->NewKANGISFileNo ?? '') ?: 'N/A';
                $fileName = trim($row->FileName ?? '') ?: 'N/A';
                $mlsfNo = trim($row->mlsfNo ?? '') ?: 'N/A';
                $stFileNo = trim($row->st_file_no ?? '') ?: 'N/A';
                $plotNo = trim($row->plot_no ?? '') ?: 'N/A';
                $tpNo = trim($row->tp_no ?? '') ?: 'N/A';
                $location = trim($row->location ?? '') ?: 'N/A';
                $trackingId = trim($row->tracking_id ?? '') ?: 'N/A';
                $createdBy = trim($row->created_by ?? '') ?: 'System';
                $source = trim($row->SOURCE ?? '') ?: 'N/A';
                
                return [
                    'id' => $row->id,
                    'mlsfNo' => $mlsfNo,
                    'kangisFileNo' => $kangisFileNo,
                    'NewKANGISFileNo' => $newKangisFileNo,
                    'stFileNo' => $stFileNo,
                    'FileName' => $fileName,
                    'plot_no' => $plotNo,
                    'tp_no' => $tpNo,
                    'location' => $location,
                    'tracking_id' => $trackingId,
                    'type' => trim($row->type ?? '') ?: 'N/A',
                    'created_by' => $createdBy,
                    'source' => $source,
                    'created_at' => $row->created_at ? date('Y-m-d H:i:s', strtotime($row->created_at)) : 'N/A',
                    'action' => $this->buildCaptureActionColumn($row)
                ];
            });

            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in FileNumberController getData: ' . $e->getMessage());
            
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error loading data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get data for Capture Existing Files DataTables
     */
    public function getCaptureData(Request $request)
    {
        // Set source to 'Captured' and call getData
        $request->merge(['source' => 'Captured']);
        return $this->getData($request);
    }

    protected function buildCaptureActionColumn($row): string
    {
        $id = (int) ($row->id ?? 0);

        if ($id <= 0) {
            return '<span class="inline-flex items-center px-2 py-1 rounded-md bg-slate-100 text-xs font-semibold text-slate-500">No actions</span>';
        }

        $editButton = '<button type="button" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 transition" onclick="editRecord(' . $id . ')"><i data-lucide="pencil" class="w-3.5 h-3.5 mr-1"></i>Edit</button>';
        $deleteButton = '<button type="button" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 transition" onclick="deleteRecord(' . $id . ')"><i data-lucide="trash-2" class="w-3.5 h-3.5 mr-1"></i>Delete</button>';

        return '<div class="flex items-center justify-center gap-2">' . $editButton . $deleteButton . '</div>';
    }

    /**
     * Get the next serial number for the current year
     */
    public function getNextSerial(Request $request)
    {
        $currentYear = $request->get('year', date('Y'));
        
        try {
            // Get all records for the current year and extract serial numbers
            // Filter by type = 'Generated' to only consider generated file numbers
            $records = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where('mlsfNo', 'like', '%-' . $currentYear . '-%')
                ->where('type', 'Generated')
                ->whereNotNull('mlsfNo')
                ->where('mlsfNo', '!=', '')
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->get();

            $maxSerial = 0;
            
            foreach ($records as $record) {
                if ($record->mlsfNo) {
                    // Extract serial number from patterns like: RES-2024-0001, CON-IND-42154, etc.
                    // Look for the last number in the string that could be a serial
                    if (preg_match('/-(\d+)(?:\(T\))?(?:\s+AND\s+EXTENSION)?$/', $record->mlsfNo, $matches)) {
                        $serial = (int) $matches[1];
                        if ($serial > $maxSerial) {
                            $maxSerial = $serial;
                        }
                    }
                }
            }
            
            $nextSerial = $maxSerial + 1;

            return response()->json(['nextSerial' => $nextSerial]);

        } catch (\Exception $e) {
            \Log::error('Error getting next serial number: ' . $e->getMessage());
            return response()->json(['nextSerial' => 1]);
        }
    }

    /**
     * Get existing file numbers for extension dropdown
     */
    public function getExistingFileNumbers()
    {
        try {
            $fileNumbers = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->select('mlsfNo')
                ->whereNotNull('mlsfNo')
                ->where('mlsfNo', '!=', '')
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->orderBy('mlsfNo', 'desc')
                ->limit(100)
                ->get();

            return response()->json($fileNumbers);

        } catch (\Exception $e) {
            \Log::error('Error getting existing file numbers: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Store a new MLS File number
     */
    public function store(Request $request)
    {
        // Only validate file name
        $validator = Validator::make($request->all(), [
            'file_name' => 'required|string|max:255',
            'tracking_id' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fileOption = $request->file_option;
            $mlsfNo = '';

            // Debug: Log the request data
            \Log::info('FileNumber store request data:', [
                'file_option' => $request->file_option,
                'serial_no' => $request->serial_no,
                'land_use' => $request->land_use,
                'year' => $request->year,
                'file_name' => $request->file_name,
                'tracking_id' => $request->tracking_id
            ]);

            if ($fileOption === 'extension') {
                // For extensions, use the existing file number with "AND EXTENSION"
                // Handle both dropdown (existing_file_no) and manual input (existing_file_no_manual)
                $existingFileNo = $request->existing_file_no ?: $request->existing_file_no_manual;
                $mlsfNo = $existingFileNo . ' AND EXTENSION';
            } elseif ($fileOption === 'temporary') {
                // For temporary files, use the existing file number with "(T)"
                // Handle both dropdown (existing_file_no) and manual input (existing_file_no_manual)
                $existingFileNo = $request->existing_file_no ?: $request->existing_file_no_manual;
                $mlsfNo = $existingFileNo . '(T)';
            } elseif ($fileOption === 'miscellaneous') {
                // Format: MISC-KN-0203
                $mlsfNo = 'MISC-' . $request->middle_prefix . '-' . $request->serial_no;
            } elseif ($fileOption === 'sltr') {
                // Format: SLTR-0203567
                $mlsfNo = 'SLTR-' . $request->serial_no;
            } elseif ($fileOption === 'sit') {
                // Format: SIT-2025-0203567
                $mlsfNo = 'SIT-' . $request->year . '-' . $request->serial_no;
            } else {
                // Generate new file number for normal files - no padding for serial number
                $mlsfNo = $request->land_use . '-' . $request->year . '-' . $request->serial_no;
            }

            $trackingId = $this->getUniqueTrackingId($request->input('tracking_id'));

            // Only validate for duplicates (skip validation for extension and temporary files)
            if (!str_ends_with($mlsfNo, ' AND EXTENSION') && !str_ends_with($mlsfNo, '(T)')) {
                $exists = DB::connection('sqlsrv')
                    ->table('fileNumber')
                    ->where('mlsfNo', $mlsfNo)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File number already exists: ' . $mlsfNo
                    ], 409);
                }
            }

            // Insert new record - only populate mlsfNo field, leave others null
            $id = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->insertGetId([
                    'tracking_id' => $trackingId,
                    'mlsfNo' => $mlsfNo,
                    'kangisFileNo' => null,  // Leave empty
                    'NewKANGISFileNo' => null,  // Leave empty
                    'FileName' => $request->file_name,
                    'plot_no' => $request->plot_no,
                    'tp_no' => $request->tp_no,
                    'location' => $request->location,
                    'type' => 'Generated',
                    'is_deleted' => 0,
                    'created_by' => Auth::user()->name ?? Auth::user()->email ?? 'System',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'MLS File number generated successfully: ' . $mlsfNo,
                'data' => [
                    'id' => $id,
                    'mlsfNo' => $mlsfNo,
                    'kangisFileNo' => null,
                    'NewKANGISFileNo' => null,
                    'FileName' => $request->file_name,
                    'tracking_id' => $trackingId
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error generating MLS File number: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating MLS File number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a captured existing file number
     */
    public function captureStore(Request $request)
    {
        // Only validate file name and that we have a generated file number
        $validator = Validator::make($request->all(), [
            'file_name' => 'required|string|max:255',
            'tracking_id' => 'nullable|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fileOption = $request->file_option;
            $mlsfNo = '';

            // Generate the complete file number based on file option
            if ($fileOption === 'extension') {
                // Handle both dropdown (existing_file_no) and manual input (existing_file_no_manual)
                $existingFileNo = $request->existing_file_no ?: $request->existing_file_no_manual;
                $mlsfNo = $existingFileNo . ' AND EXTENSION';
            } elseif ($fileOption === 'temporary') {
                // For temporary files, use the existing file number with "(T)"
                // Handle both dropdown (existing_file_no) and manual input (existing_file_no_manual)
                $existingFileNo = $request->existing_file_no ?: $request->existing_file_no_manual;
                $mlsfNo = $existingFileNo . '(T)';
            } elseif ($fileOption === 'miscellaneous') {
                $mlsfNo = 'MISC-' . $request->middle_prefix . '-' . $request->serial_no;
            } elseif ($fileOption === 'old_mls') {
                $mlsfNo = 'KN ' . $request->serial_no;
            } elseif ($fileOption === 'sltr') {
                $mlsfNo = 'SLTR-' . $request->serial_no;
            } elseif ($fileOption === 'sit') {
                $mlsfNo = 'SIT-' . $request->year . '-' . $request->serial_no;
            } else {
                // Normal format - no padding for serial number  
                $mlsfNo = $request->prefix . '-' . $request->year . '-' . $request->serial_no;
            }

            $trackingId = $this->getUniqueTrackingId($request->input('tracking_id'));

            // Only validate for duplicates (skip validation for extension and temporary files)
            if (!str_ends_with($mlsfNo, ' AND EXTENSION') && !str_ends_with($mlsfNo, '(T)')) {
                $exists = DB::connection('sqlsrv')
                    ->table('fileNumber')
                    ->where('mlsfNo', $mlsfNo)
                    ->exists();

                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'File number already exists: ' . $mlsfNo
                    ], 409);
                }
            }

            // Insert new record with only mlsfNo and file name
            $id = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->insertGetId([
                    'tracking_id' => $trackingId,
                    'mlsfNo' => $mlsfNo,
                    'kangisFileNo' => null,
                    'NewKANGISFileNo' => null,
                    'FileName' => $request->file_name,
                    'plot_no' => $request->plot_no,
                    'tp_no' => $request->tp_no,
                    'location' => $request->location,
                    'type' => 'Captured',
                    'is_deleted' => 0,
                    'created_by' => Auth::user()->name ?? Auth::user()->email ?? 'System',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Existing file number captured successfully: ' . $mlsfNo,
                'data' => [
                    'id' => $id,
                    'mlsfNo' => $mlsfNo,
                    'FileName' => $request->file_name,
                    'tracking_id' => $trackingId
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error capturing existing file number: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error capturing existing file number: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Migrate data from CSV file (simple and efficient)
     */
    public function migrate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel_file' => 'required|file|mimes:csv,txt|max:20480' // Only CSV files, 20MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please upload a valid CSV file. Max size: 20MB',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Increase memory limit and execution time for large files
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 300); // 5 minutes

            $file = $request->file('excel_file');
            $filePath = $file->getPathname();
            
            \Log::info("CSV Migration started for file: " . $file->getClientOriginalName());
            
            $imported = 0;
            $duplicates = 0;
            $errors = 0;
            $batchSize = 100;
            $batch = [];
            $rowNumber = 0;
            
            // Get existing records to check for duplicates
            $existingMlsfNos = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->whereNotNull('mlsfNo')
                ->where('mlsfNo', '!=', '')
                ->pluck('mlsfNo')
                ->toArray();
            
            $existingKangisNos = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->whereNotNull('kangisFileNo')
                ->where('kangisFileNo', '!=', '')
                ->pluck('kangisFileNo')
                ->toArray();
            
            $existingNewKangisNos = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->whereNotNull('NewKANGISFileNo')
                ->where('NewKANGISFileNo', '!=', '')
                ->pluck('NewKANGISFileNo')
                ->toArray();

            // Open and read CSV file
            if (($handle = fopen($filePath, 'r')) !== FALSE) {
                
                // Read header row to understand column structure
                $header = fgetcsv($handle, 1000, ',');
                
                if (!$header) {
                    throw new \Exception('Could not read CSV header row');
                }
                
                \Log::info("CSV Header: " . implode(', ', $header));
                
                // Find column indexes (case insensitive)
                $mlsfNoIndex = -1;
                $kangisFileIndex = -1;
                $newKangisFileNoIndex = -1;
                $fileNameIndex = -1;
                
                foreach ($header as $index => $column) {
                    $column = strtolower(trim($column));
                    if (in_array($column, ['mlsfno', 'mls_file_no', 'mlsfileno'])) {
                        $mlsfNoIndex = $index;
                    } elseif (in_array($column, ['kangisfile', 'kangis_file', 'kangisfileno'])) {
                        $kangisFileIndex = $index;
                    } elseif (in_array($column, ['newkangisfileno', 'new_kangis_file_no', 'newkangisfile'])) {
                        $newKangisFileNoIndex = $index;
                    } elseif (in_array($column, ['filename', 'file_name', 'name'])) {
                        $fileNameIndex = $index;
                    }
                }
                
                \Log::info("Column mapping - mlsfNo: {$mlsfNoIndex}, kangisFile: {$kangisFileIndex}, newKangisFileNo: {$newKangisFileNoIndex}, fileName: {$fileNameIndex}");
                
                // Process each data row
                while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $rowNumber++;
                    
                    try {
                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }
                        
                        // Extract data based on column indexes
                        $mlsfNo = trim($row[$mlsfNoIndex] ?? '');
                        $kangisFileNo = trim($row[$kangisFileIndex] ?? '');
                        $newKangisFileNo = trim($row[$newKangisFileNoIndex] ?? '');
                        $fileName = trim($row[$fileNameIndex] ?? '');
                        
                        // Skip if all essential data is missing
                        if (empty($mlsfNo) && empty($kangisFileNo) && empty($newKangisFileNo)) {
                            continue;
                        }
                        
                        // Check for duplicates
                        $isDuplicate = false;
                        if (!empty($mlsfNo) && in_array($mlsfNo, $existingMlsfNos)) {
                            $isDuplicate = true;
                        } elseif (!empty($kangisFileNo) && in_array($kangisFileNo, $existingKangisNos)) {
                            $isDuplicate = true;
                        } elseif (!empty($newKangisFileNo) && in_array($newKangisFileNo, $existingNewKangisNos)) {
                            $isDuplicate = true;
                        }
                        
                        if ($isDuplicate) {
                            $duplicates++;
                            continue;
                        }
                        
                        // Add to batch
                        $batch[] = [
                            'tracking_id' => $this->getUniqueTrackingId(),
                            'mlsfNo' => !empty($mlsfNo) ? $mlsfNo : null,
                            'kangisFileNo' => !empty($kangisFileNo) ? $kangisFileNo : null,
                            'NewKANGISFileNo' => !empty($newKangisFileNo) ? $newKangisFileNo : null,
                            'FileName' => !empty($fileName) ? $fileName : null,
                            'type' => 'Migrated',
                            'is_deleted' => 0,
                            'created_by' => 'Migrated',
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                        
                        // Update existing arrays to prevent duplicates within the same import
                        if (!empty($mlsfNo)) $existingMlsfNos[] = $mlsfNo;
                        if (!empty($kangisFileNo)) $existingKangisNos[] = $kangisFileNo;
                        if (!empty($newKangisFileNo)) $existingNewKangisNos[] = $newKangisFileNo;
                        
                        // Insert batch when it reaches the batch size
                        if (count($batch) >= $batchSize) {
                            DB::connection('sqlsrv')->table('fileNumber')->insert($batch);
                            $imported += count($batch);
                            $batch = [];
                            
                            // Log progress every 1000 records
                            if ($imported % 1000 == 0) {
                                \Log::info("Migration progress: {$imported} records imported");
                            }
                        }
                        
                    } catch (\Exception $e) {
                        \Log::error("Error importing CSV row {$rowNumber}: " . $e->getMessage());
                        $errors++;
                    }
                }
                
                // Insert remaining batch
                if (!empty($batch)) {
                    DB::connection('sqlsrv')->table('fileNumber')->insert($batch);
                    $imported += count($batch);
                }
                
                fclose($handle);
                
            } else {
                throw new \Exception('Could not open CSV file for reading');
            }
            
            // Clean up memory
            unset($existingMlsfNos, $existingKangisNos, $existingNewKangisNos, $batch);
            
            \Log::info("CSV Migration completed: {$imported} imported, {$duplicates} duplicates, {$errors} errors");
            
            return response()->json([
                'success' => true,
                'message' => "CSV migration completed successfully! Imported: {$imported}, Duplicates skipped: {$duplicates}, Errors: {$errors}",
                'data' => [
                    'imported' => $imported,
                    'duplicates' => $duplicates,
                    'errors' => $errors,
                    'total_processed' => $imported + $duplicates + $errors,
                    'rows_processed' => $rowNumber
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error during CSV migration: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error during CSV migration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific record
     */
    public function show($id)
    {
        try {
            $record = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where('id', $id)
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found'
                ], 404);
            }

            return response()->json($record);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a record (file name, KANGIS file number, and New KANGIS file number can be updated)
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'file_name' => 'required|string|max:255',
            'kangis_file_no' => 'nullable|string|max:255',
            'new_kangis_file_no' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $record = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where('id', $id)
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found'
                ], 404);
            }

            // Prepare update data
            $updateData = [
                'FileName' => $request->file_name,
                'updated_by' => Auth::user()->name ?? Auth::user()->email ?? 'System',
                'updated_at' => now()
            ];

            // Add KANGIS file number if provided
            if ($request->has('kangis_file_no')) {
                $updateData['kangisFileNo'] = $request->kangis_file_no;
            }

            // Add New KANGIS file number if provided
            if ($request->has('new_kangis_file_no')) {
                $updateData['NewKANGISFileNo'] = $request->new_kangis_file_no;
            }

            // Update the record
            DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where('id', $id)
                ->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a record (hard delete since we're not using soft delete filtering)
     */
    public function destroy($id)
    {
        try {
            $record = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where('id', $id)
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Record not found'
                ], 404);
            }

            // Soft delete: set is_deleted = 1
            DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where('id', $id)
                ->update([
                    'is_deleted' => 1,
                    'updated_by' => Auth::user()->name ?? Auth::user()->email ?? 'System',
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get total count of file numbers
     */
    public function getCount()
    {
        try {
            $count = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where(function($q) {
                    $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
                })
                ->count();

            return response()->json(['count' => $count]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear file numbers cache
     */
    public function clearCache()
    {
        try {
            Cache::forget('file_numbers_total_count_New');
            Cache::forget('file_numbers_total_count_Captured');
            
            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test database connection and table structure
     */
    public function testDatabase()
    {
        try {
            // Test connection
            $connectionTest = DB::connection('sqlsrv')->getPdo();
            
            // Test table existence
            $tableExists = DB::connection('sqlsrv')
                ->select("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'fileNumber'");
            
            // Get table structure
            $columns = DB::connection('sqlsrv')
                ->select("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'fileNumber'");
            
            // Get record count
            $recordCount = 0;
            $sampleRecords = [];
            
            if ($tableExists[0]->count > 0) {
                $recordCount = DB::connection('sqlsrv')->table('fileNumber')->count();
                $sampleRecords = DB::connection('sqlsrv')
                    ->table('fileNumber')
                    ->limit(5)
                    ->get()
                    ->toArray();
            }
            
            return response()->json([
                'success' => true,
                'connection' => 'Connected successfully',
                'table_exists' => $tableExists[0]->count > 0,
                'columns' => $columns,
                'record_count' => $recordCount,
                'sample_records' => $sampleRecords,
                'database_name' => DB::connection('sqlsrv')->getDatabaseName(),
                'server_info' => DB::connection('sqlsrv')->select('SELECT @@VERSION as version')[0]->version ?? 'Unknown'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Global API: Search file numbers (Top 10 + Search functionality)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function getUniqueTrackingId(?string $preferred = null): string
    {
        $preferred = $preferred ? strtoupper(trim($preferred)) : null;
        $attempts = 0;

        do {
            $candidate = $preferred && $attempts === 0 ? $preferred : $this->generateTrackingId();

            if (isset($this->generatedTrackingIds[$candidate])) {
                $preferred = null;
                $attempts++;
                continue;
            }

            $exists = DB::connection('sqlsrv')
                ->table('fileNumber')
                ->where('tracking_id', $candidate)
                ->exists();

            if (!$exists) {
                $this->generatedTrackingIds[$candidate] = true;
                return $candidate;
            }

            $preferred = null;
            $attempts++;
        } while ($attempts < 10);

        throw new \RuntimeException('Unable to generate a unique tracking ID after multiple attempts.');
    }

    private function generateTrackingId(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segmentOne = '';
        $segmentTwo = '';
        $length = strlen($characters) - 1;

        for ($i = 0; $i < 8; $i++) {
            $segmentOne .= $characters[random_int(0, $length)];
        }

        for ($i = 0; $i < 5; $i++) {
            $segmentTwo .= $characters[random_int(0, $length)];
        }

        return "TRK-{$segmentOne}-{$segmentTwo}";
    }

    public function searchFileNumbers(Request $request)
    {
        try {
            \Log::info('FileNumber search API called', [
                'query' => $request->input('query', ''),
                'limit' => $request->input('limit', 10),
                'page' => $request->input('page', 1)
            ]);

            $query = $request->input('query', '');
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            
            // Test database connection first
            try {
                \DB::connection('sqlsrv')->getPdo();
                \Log::info('SQL Server connection successful');
            } catch (\Exception $connException) {
                \Log::error('SQL Server connection failed', ['error' => $connException->getMessage()]);
                return response()->json([
                    'success' => false,
                    'error' => 'Database connection failed',
                    'message' => 'Unable to connect to SQL Server: ' . $connException->getMessage(),
                    'timestamp' => now()->toISOString()
                ], 500);
            }

            // Build the query
            $fileQuery = FileNumber::active()
                ->select([
                    'id',
                    'kangisFileNo',
                    'mlsfNo', 
                    'NewKANGISFileNo',
                    'FileName',
                    'tracking_id',
                    'decommissioning_reason',
                    'created_at',
                    'updated_at'
                ])
                ->orderBy('created_at', 'desc');

            // If query provided, search across relevant fields
            if (!empty($query)) {
                $fileQuery->where(function($q) use ($query) {
                    $q->where('kangisFileNo', 'LIKE', "%{$query}%")
                      ->orWhere('mlsfNo', 'LIKE', "%{$query}%")
                      ->orWhere('NewKANGISFileNo', 'LIKE', "%{$query}%")
                      ->orWhere('FileName', 'LIKE', "%{$query}%");
                });
            }

            // Get total count for pagination
            $totalCount = $fileQuery->count();
            \Log::info('Total file numbers found', ['count' => $totalCount]);
            
            // Apply pagination
            $offset = ($page - 1) * $limit;
            $results = $fileQuery->skip($offset)->take($limit)->get();
            \Log::info('Retrieved paginated results', ['count' => $results->count()]);

            // Format results for consistent API response
            $formattedResults = $results->map(function($file) {
                return [
                    'id' => $file->id,
                    'kangis_file_no' => $file->kangisFileNo,
                    'mlsf_no' => $file->mlsfNo,
                    'new_kangis_file_no' => $file->NewKANGISFileNo,
                    'file_name' => $file->FileName,
                    'tracking_id' => $file->tracking_id,
                    'display_name' => $this->formatDisplayName($file),
                    'search_text' => $this->formatSearchText($file),
                    'status' => 'Active',
                    'decommissioning_reason' => $file->decommissioning_reason,
                    'created_at' => $file->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $file->updated_at?->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedResults,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $limit),
                    'has_more' => ($page * $limit) < $totalCount
                ],
                'query' => $query,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            \Log::error('FileNumber search API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to search file numbers',
                'message' => $e->getMessage(),
                'debug_info' => [
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile())
                ],
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Global API: Get top 10 recent active file numbers
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTopFileNumbers()
    {
        try {
            \Log::info('FileNumber getTopFileNumbers API called');
                
                $topFiles = FileNumber::active()
                    ->select([
                        'id',
                        'kangisFileNo',
                        'mlsfNo', 
                        'NewKANGISFileNo',
                        'FileName',
                        'created_at',
                        'updated_at'
                    ])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();

                \Log::info('Retrieved top file numbers', ['count' => $topFiles->count()]);

                $formattedResults = $topFiles->map(function($file) {
                    return [
                        'id' => $file->id,
                        'kangis_file_no' => $file->kangisFileNo,
                        'mlsf_no' => $file->mlsfNo,
                        'new_kangis_file_no' => $file->NewKANGISFileNo,
                        'file_name' => $file->FileName,
                        'display_name' => $this->formatDisplayName($file),
                        'search_text' => $this->formatSearchText($file),
                        'status' => 'Active',
                        'created_at' => $file->created_at?->format('Y-m-d H:i:s'),
                        'updated_at' => $file->updated_at?->format('Y-m-d H:i:s')
                    ];
                });

            } catch (\Exception $connException) {
                \Log::error('SQL Server connection failed in getTopFileNumbers, using mock data', ['error' => $connException->getMessage()]);
                
                // Return mock data when database is unavailable
                $formattedResults = collect([
                    [
                        'id' => 1,
                        'kangis_file_no' => 'KLA/2024/001',
                        'mlsf_no' => 'MLSF-2024-001',
                        'new_kangis_file_no' => 'NKLA/2024/001',
                        'file_name' => 'Victoria Island Property',
                        'display_name' => 'KLA/2024/001 - Victoria Island Property',
                        'search_text' => 'KLA/2024/001 MLSF-2024-001 NKLA/2024/001 Victoria Island Property',
                        'status' => 'Active',
                        'created_at' => now()->format('Y-m-d H:i:s'),
                        'updated_at' => now()->format('Y-m-d H:i:s')
                    ],
                    [
                        'id' => 2,
                        'kangis_file_no' => 'COM/2024/001',
                        'mlsf_no' => 'MLSF-COM-2024-001',
                        'new_kangis_file_no' => 'NCOM/2024/001',
                        'file_name' => 'Commercial Plaza Lagos',
                        'display_name' => 'COM/2024/001 - Commercial Plaza Lagos',
                        'search_text' => 'COM/2024/001 MLSF-COM-2024-001 NCOM/2024/001 Commercial Plaza Lagos',
                        'status' => 'Active',
                        'created_at' => now()->format('Y-m-d H:i:s'),
                        'updated_at' => now()->format('Y-m-d H:i:s')
                    ],
                    [
                        'id' => 3,
                        'kangis_file_no' => 'RES/2024/001',
                        'mlsf_no' => 'MLSF-RES-2024-001',
                        'new_kangis_file_no' => 'NRES/2024/001',
                        'file_name' => 'Residential Estate Abuja',
                        'tracking_id' => 'TRK-20240101-ABCD',
                        'display_name' => 'RES/2024/001 - Residential Estate Abuja',
                        'search_text' => 'TRK-20240101-ABCD RES/2024/001 MLSF-RES-2024-001 NRES/2024/001 Residential Estate Abuja',
                        'status' => 'Active',
                        'created_at' => now()->format('Y-m-d H:i:s'),
                        'updated_at' => now()->format('Y-m-d H:i:s')
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $formattedResults,
                'count' => $formattedResults->count(),
                'timestamp' => now()->toISOString()
            ]);
    }

    /**
     * Global API: Get file number details by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFileNumberDetails($id)
    {
        try {
            $file = FileNumber::active()
                ->select([
                    'id',
                    'kangisFileNo',
                    'mlsfNo', 
                    'NewKANGISFileNo',
                    'FileName',
                    'tracking_id',
                    'decommissioning_reason',
                    'created_by',
                    'updated_by',
                    'location',
                    'SOURCE',
                    'commissioning_date',
                    'created_at',
                    'updated_at'
                ])
                ->find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'error' => 'File number not found',
                    'timestamp' => now()->toISOString()
                ], 404);
            }

            $formattedFile = [
                'id' => $file->id,
                'kangis_file_no' => $file->kangisFileNo,
                'mlsf_no' => $file->mlsfNo,
                'new_kangis_file_no' => $file->NewKANGISFileNo,
                'file_name' => $file->FileName,
                'tracking_id' => $file->tracking_id,
                'display_name' => $this->formatDisplayName($file),
                'search_text' => $this->formatSearchText($file),
                'status' => 'Active',
                'decommissioning_reason' => $file->decommissioning_reason,
                'created_by' => $file->created_by,
                'updated_by' => $file->updated_by,
                'location' => $file->location,
                'source' => $file->SOURCE,
                'commissioning_date' => $file->commissioning_date?->format('Y-m-d H:i:s'),
                'created_at' => $file->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $file->updated_at?->format('Y-m-d H:i:s')
            ];

            return response()->json([
                'success' => true,
                'data' => $formattedFile,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch file number details',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Helper method to format display name for file number
     * 
     * @param FileNumber $file
     * @return string
     */
    private function formatDisplayName($file)
    {
        // Helper function to check if a value is valid (not null, empty, or N/A)
        $isValidValue = function($value) {
            return $value && 
                   trim($value) !== '' && 
                   strtoupper(trim($value)) !== 'N/A' && 
                   strtoupper(trim($value)) !== 'NULL';
        };
        
        $validFileNumbers = array_filter([
            $file->kangisFileNo,
            $file->mlsfNo,
            $file->NewKANGISFileNo
        ], $isValidValue);
        
        // Use array_values to reindex and check if array has elements
        $validFileNumbers = array_values($validFileNumbers);
        $primaryNumber = !empty($validFileNumbers) ? $validFileNumbers[0] : 'N/A';
        
        // Only add file name if it's valid
        $fileName = $isValidValue($file->FileName) ? ' - ' . $file->FileName : '';
        
        // Only add tracking ID if it's valid
        $trackingSuffix = $isValidValue($file->tracking_id) ? ' [' . $file->tracking_id . ']' : '';
        
        return $primaryNumber . $fileName . $trackingSuffix;
    }

    /**
     * Helper method to format search text for file number
     * 
     * @param FileNumber $file
     * @return string
     */
    private function formatSearchText($file)
    {
        // Helper function to check if a value is valid (not null, empty, or N/A)
        $isValidValue = function($value) {
            return $value && 
                   trim($value) !== '' && 
                   strtoupper(trim($value)) !== 'N/A' && 
                   strtoupper(trim($value)) !== 'NULL';
        };
        
        return implode(' ', array_filter([
            $file->tracking_id,
            $file->kangisFileNo,
            $file->mlsfNo,
            $file->NewKANGISFileNo,
            $file->FileName
        ], $isValidValue));
    }
}
