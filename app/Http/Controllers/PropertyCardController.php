<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\PropertyRecord;

class PropertyCardController extends Controller
{
    public function index()
    {
        $PageTitle = 'Property Record Assistant';
        $PageDescription = '';
        
        // Get property records but exclude CofO records
        // Assuming CofO records have a specific identifier or are in a different table
        $Property_records = DB::connection('sqlsrv')
            ->table('property_records')
            ->where('mlsfNo', '!=', '')
            ->where('kangisFileNo', '!=', '')
            // Exclude CofO records - assuming they have a specific title_type or instrument_type
            ->where(function($query) {
                $query->where('title_type', '!=', 'Certificate of Occupancy')
                      ->orWhereNull('title_type');
            })
            ->where(function($query) {
                $query->where('instrument_type', '!=', 'Certificate of Occupancy')
                      ->orWhereNull('instrument_type');
            })
            ->orderBy('id', 'desc')  
            ->get();

        $pageLength = 50; // set default page length
        return view('propertycard.index', compact('pageLength', 'PageTitle', 'PageDescription', 'Property_records'));
    }
     public function capture(Request $request){
         
        
        return view('propertycard.capture');
    }

    public function getData(Request $request)
    {
        try {
            // Get DataTables parameters
            $draw = $request->input('draw', 1);
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $searchValue = $request->input('search.value', '');
            
            // Get ordering parameters
            $orderColumn = $request->input('order.0.column', 5); // Default to transaction_date column (index 5)
            $orderDir = $request->input('order.0.dir', 'desc');
            
            // Define column mapping for ordering
            $columns = [
                0 => 'kangisFileNo', // File Number (composite in UI; using kangisFileNo for ordering)
                1 => 'property_description',
                2 => 'location',
                3 => 'regNo',
                4 => 'transaction_type', // Changed back to transaction_type for Instrument Type column
                5 => 'transaction_date',
                6 => 'created_at', // DATE CAPTURED
                7 => 'id' // Actions column
            ];
            
            // Build base query and exclude CofO records
            $query = DB::connection('sqlsrv')->table('property_records');
            
            // Exclude CofO records from the main property records table
            $query->where(function($q) {
                $q->where('title_type', '!=', 'Certificate of Occupancy')
                  ->orWhereNull('title_type');
            })
            ->where(function($q) {
                $q->where('instrument_type', '!=', 'Certificate of Occupancy')
                  ->orWhereNull('instrument_type');
            });
            
            // Apply search if provided
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('mlsfNo', 'like', "%{$searchValue}%")
                      ->orWhere('kangisFileNo', 'like', "%{$searchValue}%")
                      ->orWhere('NewKANGISFileno', 'like', "%{$searchValue}%")
                      ->orWhere('property_description', 'like', "%{$searchValue}%")
                      ->orWhere('location', 'like', "%{$searchValue}%")
                      ->orWhere('regNo', 'like', "%{$searchValue}%")
                      ->orWhere('transaction_type', 'like', "%{$searchValue}%")
                      ->orWhere('instrument_type', 'like', "%{$searchValue}%");
                });
            }
            
            // Get total count before applying pagination (excluding CofO records)
            $recordsTotal = DB::connection('sqlsrv')->table('property_records')
                ->where(function($q) {
                    $q->where('title_type', '!=', 'Certificate of Occupancy')
                      ->orWhereNull('title_type');
                })
                ->where(function($q) {
                    $q->where('instrument_type', '!=', 'Certificate of Occupancy')
                      ->orWhereNull('instrument_type');
                })
                ->count();
            $recordsFiltered = $query->count();
            
            // Apply ordering
            $orderColumnName = $columns[$orderColumn] ?? 'id';
            
            // Special handling for transaction_date ordering to prioritize newest records
            if ($orderColumnName === 'transaction_date') {
                // Order by transaction_date first, then by id for records with same or null transaction_date
                $query->orderByRaw("CASE WHEN transaction_date IS NULL THEN 1 ELSE 0 END")
                      ->orderBy('transaction_date', $orderDir)
                      ->orderBy('id', 'desc'); // Always show newest records first as secondary sort
            } else {
                $query->orderBy($orderColumnName, $orderDir);
            }
            
            // Apply pagination
            $records = $query->skip($start)->take($length)->get();
            
            // Format the data for DataTables
            $data = [];
            foreach ($records as $record) {
                $data[] = [
                    'id' => $record->id,
                    'kangisFileNo' => $record->kangisFileNo,
                    'mlsFNo' => $record->mlsFNo,
                    'NewKANGISFileno' => $record->NewKANGISFileno,
                    'property_description' => $record->property_description,
                    'location' => $record->location,
                    'regNo' => $record->regNo,
                    'transaction_type' => $record->transaction_type,
                    'instrument_type' => $record->instrument_type,
                    'transaction_date' => $record->transaction_date,
                    'created_at' => $record->created_at,
                ];
            }
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getData: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error retrieving data: ' . $e->getMessage()
            ]);
        }
    }

public function search(Request $request)
{
    \Log::info('Search request received:', $request->all());

    $query = DB::table('property_records');
    $hasConditions = false;

    // Handle each possible search field
    if ($request->filled('kangisFileNo')) {
        $query->where('kangisFileNo', 'LIKE', '%' . $request->kangisFileNo . '%');
        $hasConditions = true;
    }

    if ($request->filled('mlsfNo')) {
        $query->where('mlsfNo', 'LIKE', '%' . $request->mlsfNo . '%');
        $hasConditions = true;
    }

    if ($request->filled('oldTitleSerialNo')) {
        $query->where('oldTitleSerialNo', 'LIKE', '%' . $request->oldTitleSerialNo . '%');
        $hasConditions = true;
    }

    if ($request->filled('oldTitlePageNo')) {
        $query->where('oldTitlePageNo', 'LIKE', '%' . $request->oldTitlePageNo . '%');
        $hasConditions = true;
    }

    if ($request->filled('oldTitleVolumeNo')) {
        $query->where('oldTitleVolumeNo', 'LIKE', '%' . $request->oldTitleVolumeNo . '%');
        $hasConditions = true;
    }

    if ($request->filled('lgaName')) {
        $query->where('lgaName', 'LIKE', '%' . $request->lgaName . '%');
        $hasConditions = true;
    }

    if ($request->filled('originalAllottee')) {
        $query->where('originalAllottee', 'LIKE', '%' . $request->originalAllottee . '%');
        $hasConditions = true;
    }

    if ($request->filled('currentAllottee')) {
        $query->where('currentAllottee', 'LIKE', '%' . $request->currentAllottee . '%');
        $hasConditions = true;
    }

    if (!$hasConditions) {
        return response()->json(['success' => false, 'message' => 'No search criteria provided']);
    }

    $result = $query->first();
    \Log::info('Search result:', ['result' => $result]);

    if (!$result) {
        return response()->json(['success' => false, 'message' => 'No matching records found']);
    }

    // Map the database fields to view fields
    $kangisFileNo = $result->kangisFileNo ?? '';
    $fileNoPrefix = '';
    $fileNumber = '';

    // Safely split kangisFileNo into prefix and number
    if (!empty($kangisFileNo)) {
        $parts = explode(' ', $kangisFileNo, 2);
        $fileNoPrefix = $parts[0] ?? '';
        $fileNumber = $parts[1] ?? '';
    }

    $mappedResult = [
        'success' => true,
        'data' => [
            'id' => $result->id ?? 'N/A',
            'fileNoPrefix' => $fileNoPrefix,
            'fileNumber' =>  $fileNumber,
            'plotNo' => $result->plotNo ?: 'N/A',
            'blockNo' => $result->blockNo ?: 'N/A',
            'districtName' => $result->districtName ?: 'N/A',
            'lgaName' => $result->lgaName ?: 'N/A',
            'description' => trim(implode(' ', array_filter([
                $result->plotNo,
                $result->blockNo,
                $result->districtName,
                $result->lgaName
            ], function($value) {
                return $value !== null && $value !== '';
            }))),
            'originalAllottee' => $result->originalAllottee ?: 'N/A',
            'currentAllottee' => $result->currentAllottee ?: 'N/A',
            'oldTitleSerialNo'   => $result->oldTitleSerialNo ?: 'N/A',
            'oldTitlePageNo'    => $result->oldTitlePageNo ?: 'N/A',
            'oldTitleVolumeNo'  => $result->oldTitleVolumeNo ?: 'N/A',
            'layoutName' => $result->layoutName ?: 'N/A',
            'mlsfNo' => $result->mlsfNo ?: 'N/A',
            'kangisFileNo' => $result->kangisFileNo ?: 'N/A'
        ]
    ];

    return response()->json($mappedResult);
}


    public function create(Request $request)
    {
        $result = null;
        $recordCount = DB::table('property_records')->count();
        return view('propertycard.create', compact('result', 'recordCount'));
    }

    public function store(Request $request)
    {
        \Log::info('Store request received:', $request->all());

        $validatedData = $request->validate([
            'planid' => 'nullable|string|max:255',
            'mlsfNo' => 'nullable|string|max:255',
            'kangisFileNo' => 'nullable|string|max:255',
            'plotNo' => 'nullable|string|max:255',
            'blockNo' => 'nullable|string|max:255',
            'approvedPlanNo' => 'nullable|string|max:255',
            'tpPlanNo' => 'nullable|string|max:255',
            'surveyedBy' => 'nullable|string|max:255',
            'drawnBy' => 'nullable|string|max:255',
            'checkedBy' => 'nullable|string|max:255',
            'passedBy' => 'nullable|string|max:255',
            'plotYearCreated' => 'nullable|string|max:255',
            'beaconControlName' => 'nullable|string|max:255',
            'beaconControlX' => 'nullable|string|max:255',
            'beaconControlY' => 'nullable|string|max:255',
            'metricSheetIndex' => 'nullable|string|max:255',
            'metricSheetNo' => 'nullable|string|max:255',
            'imperialSheet' => 'nullable|string|max:255',
            'imperialSheetNo' => 'nullable|string|max:255',
            'layoutName' => 'nullable|string|max:255',
            'districtName' => 'nullable|string|max:255',
            'lgaName' => 'nullable|string|max:255',
            'streetName' => 'nullable|string|max:255',
            'houseNo' => 'nullable|string|max:255',
            'tenancy' => 'nullable|string|max:255',
            'areaInHectares' => 'nullable|string|max:255',
            'titleStatus' => 'nullable|string|max:255',
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'oldTitleSerialNo' => 'nullable|string|max:255',
            'oldTitlePageNo' => 'nullable|string|max:255',
            'oldTitleVolumeNo' => 'nullable|string|max:255',
            'deedsDate' => 'nullable|date',
            'deedsTime' => 'nullable|string|max:255',
            'certificateDate' => 'nullable|date',
            'originalAllottee' => 'nullable|string|max:255',
            'addressOfOriginalAllottee' => 'nullable|string|max:255',
            'titleIssuedYear' => 'nullable|string|max:255',
            'currentAllottee' => 'nullable|string|max:255',
            'addressOfCurrentAllottee' => 'nullable|string|max:255',
            'titleOfCurrentAllottee' => 'nullable|string|max:255',
            'currentYearTitleOwned' => 'nullable|string|max:255',
            'phoneNo' => 'nullable|string|max:255',
            'emailAddress' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'parcelNewlyDigitized' => 'nullable|string|max:255',
            'digitizationSource' => 'nullable|string|max:255',
            'workTime' => 'nullable|string|max:255',
            'electronicSupervisor' => 'nullable|string|max:255',
            'directorGeneral' => 'nullable|string|max:255',
            'evidenceOfPayment' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'transactionDocument' => 'nullable|string|max:255',
            'passportPhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'nationalId' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'internationalPassport' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'businessRegCert' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'formCO7AndCO4' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'certOfIncorporation' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'memorandumAndArticle' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'letterOfAdmin' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'courtAffidavit' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'policeReport' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'newspaperAdvert' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'acknowledgementStatus' => 'nullable|string|max:255',
            'acknowledgementIssuedDate' => 'nullable|date',
            'acknowledgementIssuedBy' => 'nullable|string|max:255',
            'bankDraftSerialNo' => 'nullable|string|max:255',
            'bankName' => 'nullable|string|max:255',
            'conflictExistence' => 'nullable|string|max:255',
            'problemNature1' => 'nullable|string|max:255',
            'problemNature2' => 'nullable|string|max:255',
            'landUse' => 'nullable|string|max:255',
            'specifically' => 'nullable|string|max:255',
        ]);

        \Log::info('Validated data:', $validatedData);

        $propertyRecord = new PropertyRecord($validatedData);

        // Handle file uploads
        $fileFields = [
            'picture',
            'passportPhoto',
            'nationalId',
            'internationalPassport',
            'businessRegCert',
            'formCO7AndCO4',
            'certOfIncorporation',
            'memorandumAndArticle',
            'letterOfAdmin',
            'courtAffidavit',
            'policeReport',
            'newspaperAdvert',
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('uploads', $filename, 'public'); // Store in the 'public' disk under 'uploads' folder
                $propertyRecord->$field = $path; // Save the path to the database
            }
        }

        $propertyRecord->save();

        return redirect()->route('propertycard.index')->with('success', 'Property record created successfully.');
    }

    public function savePropertyRecord(Request $request)
    {
        // Validate and sanitize the input
        $validatedData = $request->validate([
            'mlsfNo'                     => 'nullable|string|max:255',
            'kangisFileNo'               => 'nullable|string|max:255',
            'plotNo'                     => 'nullable|string|max:255',
            'blockNo'                    => 'nullable|string|max:255',
            'approvedPlanNo'             => 'nullable|string|max:255',
            'tpPlanNo'                   => 'nullable|string|max:255',
            'surveyedBy'                 => 'nullable|string|max:255',
            'layoutName'                 => 'nullable|string|max:255',
            'districtName'               => 'nullable|string|max:255',
            'lgaName'                    => 'nullable|string|max:255',
            'oldTitleSerialNo'           => 'nullable|string|max:255',
            'oldTitlePageNo'             => 'nullable|string|max:255',
            'oldTitleVolumeNo'           => 'nullable|string|max:255',
            'deedsDate'                  => 'nullable|date',
            'deedsTime'                  => 'nullable|string|max:255',
            'certificateDate'            => 'nullable|date',
            'originalAllottee'           => 'nullable|string|max:255',
            'addressOfOriginalAllottee'  => 'nullable|string|max:255',
            'titleIssuedYear'            => 'nullable|string|max:255',
            'changeOfOwnership'          => 'nullable|string|max:255',
            'reasonForChange'            => 'nullable|string|max:255',
            'currentAllottee'            => 'nullable|string|max:255',
            'addressOfCurrentAllottee'   => 'nullable|string|max:255',
            'titleOfCurrentAllottee'     => 'nullable|string|max:255',
            'currentYearTitleOwned'      => 'nullable|string|max:255',
            'phoneNo'                    => 'nullable|string|max:255',
            'landUse'                    => 'nullable|string|max:255',
            'specifically'               => 'nullable|string|max:255',
            'tenancy'                    => 'nullable|string|max:255',
            'areaInHectares'             => 'nullable|string|max:255',
        ]);
        
        // Create and save a new property record ensuring we use the property_records table
        $record = new \App\Models\PropertyRecord($validatedData);
        $record->setDataSource('property_records');
        $record->save();
        
        return response()->json(['success' => true]);
    }

    public function navigateRecord(Request $request)
    {
        $currentId = $request->input('currentId');
        $direction = $request->input('direction');
        
        // Query builder for property_records table
        $query = DB::table('property_records');

        // Handle different navigation directions
        switch ($direction) {
            case 'first':
                $record = $query->orderBy('id', 'asc')->first();
                break;
                
            case 'last':
                $record = $query->orderBy('id', 'desc')->first();
                break;
                
            case 'previous':
                if (empty($currentId)) {
                    $record = $query->orderBy('id', 'desc')->first();
                } else {
                    $record = $query->where('id', '<', $currentId)
                                   ->orderBy('id', 'desc')
                                   ->first();
                                   
                    if (!$record) {
                        $record = $query->orderBy('id', 'desc')->first();
                    }
                }
                break;
                
            case 'next':
                if (empty($currentId)) {
                    $record = $query->orderBy('id', 'asc')->first();
                } else {
                    $record = $query->where('id', '>', $currentId)
                                   ->orderBy('id', 'asc')
                                   ->first();
                                   
                    if (!$record) {
                        $record = $query->orderBy('id', 'asc')->first();
                    }
                }
                break;
                
            default:
                return response()->json(['success' => false, 'message' => 'Invalid navigation direction']);
        }

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'No records found']);
        }

        // Safely map kangisFileNo parts without assuming a space exists
        $fileNoPrefix = '';
        $fileNumber = '';
        if (!empty($record->kangisFileNo)) {
            $pos = strpos($record->kangisFileNo, ' ');
            if ($pos !== false) {
                $fileNoPrefix = substr($record->kangisFileNo, 0, $pos);
                $fileNumber   = substr($record->kangisFileNo, $pos + 1);
            } else {
                $fileNoPrefix = $record->kangisFileNo;
            }
        }

        $mappedResult = [
            'id'                => $record->id ?? 'N/A',
            'fileNoPrefix'      => $fileNoPrefix ?: 'N/A',
            'fileNumber'        => $fileNumber ?: 'N/A',
            'plotNo'            => $record->plotNo ?: 'N/A',
            'blockNo'           => $record->blockNo ?: 'N/A',
            'districtName'      => $record->districtName ?: 'N/A',
            'lgaName'           => $record->lgaName ?: 'N/A',
            'description'       => $record->specifically ?: 'N/A',
            'originalAllottee'  => $record->originalAllottee ?: 'N/A',
            'currentAllottee'   => $record->currentAllottee ?: 'N/A',
            'layoutName'        => $record->layoutName ?: 'N/A',
            'description' => trim(implode(' ', array_filter([
                $record->plotNo,
                $record->blockNo,
                $record->districtName,
                $record->lgaName
            ], function($value) {
                return $value !== null && $value !== '';
            }))),
            'oldTitleSerialNo'   => $record->oldTitleSerialNo ?: 'N/A',
            'oldTitlePageNo'    => $record->oldTitlePageNo ?: 'N/A',
            'oldTitleVolumeNo'  => $record->oldTitleVolumeNo ?: 'N/A',
            'mlsfNo'            => $record->mlsfNo ?: 'N/A',
            'kangisFileNo'      => $record->kangisFileNo ?: 'N/A'
        ];

        $recordCount = DB::table('property_records')->count();

        return response()->json(array_merge(['success' => true, 'recordCount' => $recordCount], $mappedResult));
    }

    public function getRecordDetails(Request $request)
    {
        try {
            $id = $request->input('id');
            
            if (!$id) {
                return response()->json(['success' => false, 'message' => 'Record ID is required']);
            }
            
            $record = DB::table('property_records')->where('id', $id)->first();
            
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Record not found']);
            }
            
            return response()->json(['success' => true, 'data' => $record]);
        } catch (\Exception $e) {
            \Log::error('Error fetching record details: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function aiIndex()
    {
        return view('propertycard.ai.index');
    }

    public function saveAiPropertyRecord(Request $request)
    {
        \Log::info('AI Property Record save request received:', $request->all());

        // Transform the AI form data to match the PropertyRecordController's expected format
        $transformedRequest = new Request();
        
        // Copy all original data first
        $transformedRequest->replace($request->all());
        
        // Robust file number handling (mirror manual behavior)
        // If caller already provided any of these, respect them
        $mlsExisting = trim((string) $request->input('mlsFNo', ''));
        $kangisExisting = trim((string) $request->input('kangisFileNo', ''));
        $newKangisExisting = trim((string) $request->input('NewKANGISFileno', ''));

        // Derive a complete file number if needed
        $fileNumber = $request->input('complete-file-no') ?: $request->input('fileno');
        if (!$fileNumber && $request->filled('file-prefix') && $request->filled('file-serial-no')) {
            $fileNumber = trim($request->input('file-prefix') . ' ' . $request->input('file-serial-no'));
        }

        // Determine which column to target
        $fileNumberType = $request->input('file-number-type');
        // Fallback to activeFileTab from AI UI (values like 'mls', 'kangis', 'newkangis')
        if (!$fileNumberType) {
            $aft = $request->input('activeFileTab');
            if ($aft) {
                $map = [
                    'mls' => 'mlsFileNo',
                    'mlsFNo' => 'mlsFileNo',
                    'kangis' => 'kangisFileNo',
                    'kangisFileNo' => 'kangisFileNo',
                    'newkangis' => 'newKangisFileNo',
                    'NewKANGISFileno' => 'newKangisFileNo',
                ];
                $fileNumberType = $map[$aft] ?? null;
            }
        }

        // If specific file number fields are missing, populate based on detected type
        if (!$mlsExisting && !$kangisExisting && !$newKangisExisting && $fileNumber) {
            switch ($fileNumberType) {
                case 'mlsFileNo':
                    $transformedRequest->merge(['mlsFNo' => $fileNumber]);
                    break;
                case 'newKangisFileNo':
                    $transformedRequest->merge(['NewKANGISFileno' => $fileNumber]);
                    break;
                case 'kangisFileNo':
                default:
                    $transformedRequest->merge(['kangisFileNo' => $fileNumber]);
                    break;
            }
        }
        // Otherwise, leave any provided mlsFNo/kangisFileNo/NewKANGISFileno as-is
        
        // Normalize street/district selections
        $streetName = $request->input('streetName');
        if ($streetName === 'other' && $request->input('otherStreetName')) {
            $streetName = $request->input('otherStreetName');
        }
        $district = $request->input('district');
        if ($district === 'other' && $request->input('otherDistrict')) {
            $district = $request->input('otherDistrict');
        }

        // Keep lgsaOrCity provided by caller; fallback to legacy "lga" param if needed
        $lgsaOrCity = $request->input('lgsaOrCity');
        if (!$lgsaOrCity) {
            $lgsaOrCity = $request->input('lga');
        }

        // Build location string similar to manual assistant
        $location = trim(implode(', ', array_filter([
            $request->input('house_no'),
            $streetName,
            $district,
            $lgsaOrCity,
            $request->input('state', 'Kano')
        ], function ($v) { return !is_null($v) && $v !== ''; })));        
        
        // Merge normalized fields
        $transformedRequest->merge([
            'location' => $location,
            'lgsaOrCity' => $lgsaOrCity, // do not overwrite with null
            'title_type' => $request->input('title_type') ?: 'STATUTORY RIGHT OF OCCUPANCY',
            'instrumentType' => $request->input('instrumentType') ?: $request->input('transactionType'),
        ]);
        
        // Use the PropertyRecordController's store method which has the correct database mapping
        $propertyRecordController = new \App\Http\Controllers\PropertyRecordController();
        return $propertyRecordController->store($transformedRequest);
    }

    public function cofoIndex()
    {
        $PageTitle = 'Other Instrument';
        $PageDescription = '';
        
        // Get CofO records for the page
        $cofoRecords = DB::connection('sqlsrv')
            ->table('CofO')
            ->orderBy('created_at', 'desc')
            ->get();

        $pageLength = 25; // set default page length for CofO records
        return view('propertycard.cofo', compact('pageLength', 'PageTitle', 'PageDescription', 'cofoRecords'));
    }

    public function getCofOData(Request $request)
    {
        try {
            // Get DataTables parameters
            $draw = $request->input('draw', 1);
            $start = $request->input('start', 0);
            $length = $request->input('length', 25);
            $searchValue = $request->input('search.value', '');
            
            // Get ordering parameters
            $orderColumn = $request->input('order.0.column', 7); // Default to created_at column (index 7)
            $orderDir = $request->input('order.0.dir', 'desc');
            
            // Define column mapping for ordering
            $columns = [
                0 => 'kangisFileNo', // File Number (composite in UI; using kangisFileNo for ordering)
                1 => 'property_description',
                2 => 'location',
                3 => 'regNo',
                4 => 'cofo_type',
                5 => 'land_use',
                6 => 'instrument_type', // Changed from transaction_date to instrument_type
                7 => 'created_at', // DATE CAPTURED
                8 => 'id' // Actions column
            ];
            
            // Build base query for CofO table
            $query = DB::connection('sqlsrv')->table('CofO');
            
            // Apply search if provided
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('mlsFNo', 'like', "%{$searchValue}%")
                      ->orWhere('kangisFileNo', 'like', "%{$searchValue}%")
                      ->orWhere('NewKANGISFileno', 'like', "%{$searchValue}%")
                      ->orWhere('property_description', 'like', "%{$searchValue}%")
                      ->orWhere('location', 'like', "%{$searchValue}%")
                      ->orWhere('regNo', 'like', "%{$searchValue}%")
                      ->orWhere('transaction_type', 'like', "%{$searchValue}%")
                      ->orWhere('cofo_type', 'like', "%{$searchValue}%")
                      ->orWhere('land_use', 'like', "%{$searchValue}%");
                });
            }
            
            // Get total count before applying pagination
            $recordsTotal = DB::connection('sqlsrv')->table('CofO')->count();
            $recordsFiltered = $query->count();
            
            // Apply ordering
            $orderColumnName = $columns[$orderColumn] ?? 'id';
            
            // Special handling for transaction_date ordering to prioritize newest records
            if ($orderColumnName === 'transaction_date') {
                // Order by transaction_date first, then by id for records with same or null transaction_date
                $query->orderByRaw("CASE WHEN transaction_date IS NULL THEN 1 ELSE 0 END")
                      ->orderBy('transaction_date', $orderDir)
                      ->orderBy('id', 'desc'); // Always show newest records first as secondary sort
            } else {
                $query->orderBy($orderColumnName, $orderDir);
            }
            
            // Apply pagination
            $records = $query->skip($start)->take($length)->get();
            
            // Format the data for DataTables
            $data = [];
            foreach ($records as $record) {
                $data[] = [
                    'id' => $record->id,
                    'kangisFileNo' => $record->kangisFileNo,
                    'mlsFNo' => $record->mlsFNo,
                    'NewKANGISFileno' => $record->NewKANGISFileno,
                    'property_description' => $record->property_description,
                    'location' => $record->location,
                    'regNo' => $record->regNo,
                    'cofo_type' => $record->cofo_type,
                    'land_use' => $record->land_use,
                    'instrument_type' => $record->instrument_type, // Changed from transaction_date to instrument_type
                    'created_at' => $record->created_at,
                ];
            }
            
            return response()->json([
                'draw' => intval($draw),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getCofOData: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'draw' => intval($request->input('draw', 1)),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error retrieving CofO data: ' . $e->getMessage()
            ]);
        }
    }
}