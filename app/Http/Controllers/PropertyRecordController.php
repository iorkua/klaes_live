<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // Add Auth facade for user tracking
use Illuminate\Support\Facades\Schema; // Schema facade to check columns safely
 
class PropertyRecordController extends Controller
{
    /**
     * Store a newly created property record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

       public function index()
    {
        $PageTitle = 'Property Record Assistant';
        $PageDescription = '';
        
        // Specify the table before using get()
        $Property_records = DB::connection('sqlsrv')->table('property_records')->orderBy('created_at', 'desc')->get();

        $pageLength = 50; // set default page length
        return view('propertycard.index', compact('pageLength', 'PageTitle', 'PageDescription', 'Property_records'));
    } 
     

    
    public function store(Request $request)
    {
        // Log the incoming request for debugging
        \Log::info('Property Record Store Request:', [
            'all_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->url(),
            'user_agent' => $request->userAgent()
        ]);

        // Updated validation rules for new field names
        $validator = Validator::make($request->all(), [
            'mlsFNo' => 'nullable|string|max:255',
            'kangisFileNo' => 'nullable|string|max:255',
            'NewKANGISFileno' => 'nullable|string|max:255',
            'fileno' => 'nullable|string|max:255',
            'title_type' => 'nullable|string|max:255', // Add title_type validation
            'transactionType' => 'nullable|string|max:255',
            'transactionDate' => 'nullable|date',
            'serialNo' => 'nullable|string|max:50',
            'pageNo' => 'nullable|string|max:50',
            'volumeNo' => 'nullable|string|max:50',
            'regDate' => 'nullable|date',
            'regTime' => 'nullable|string|max:10',
            'instrumentType' => 'nullable|string|max:255',
            'period' => 'nullable|numeric',
            'periodUnit' => 'nullable|string|max:50',
            // Party fields based on transaction type
            'Assignor' => 'nullable|string|max:500',
            'Assignee' => 'nullable|string|max:500',
            'Mortgagor' => 'nullable|string|max:500',
            'Mortgagee' => 'nullable|string|max:500',
            'Surrenderor' => 'nullable|string|max:500',
            'Surrenderee' => 'nullable|string|max:500',
            'Lessor' => 'nullable|string|max:500',
            'Lessee' => 'nullable|string|max:500',
            'Grantor' => 'nullable|string|max:500',
            'Grantee' => 'nullable|string|max:500',
            // Property details
            'property_description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'plot_no' => 'nullable|string|max:100',
            // New fields
            'lgsaOrCity' => 'nullable|string|max:255',
            'layout' => 'nullable|string|max:255',
            'schedule' => 'nullable|string|max:255',
            // Additional fields added per request
            'tp_no' => 'nullable|string|max:255',
            'lpkn_no' => 'nullable|string|max:255',
            'approved_plan_no' => 'nullable|string|max:255',
            'plot_size' => 'nullable|string|max:255',
            'date_recommended' => 'nullable|date',
            'date_approved' => 'nullable|date',
            'lease_begins' => 'nullable|date',
            'lease_expires' => 'nullable|date',
            'metric_sheet' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            \Log::error('Property Record Validation Failed:', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all()
            ]);

            // Check if request expects JSON (AJAX) or normal redirect
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Start database transaction
        DB::connection('sqlsrv')->beginTransaction();

        try {
            // First, check if the property_records table exists
            $tableExists = Schema::connection('sqlsrv')->hasTable('property_records');
            if (!$tableExists) {
                throw new \Exception('Table property_records does not exist in the database');
            }

            // Log table columns for debugging
            $columns = Schema::connection('sqlsrv')->getColumnListing('property_records');
            \Log::info('Property Records Table Columns:', $columns);

            // Normalize file numbers coming from either manual fields or smart selector
            $mls = trim((string) $request->input('mlsFNo', ''));
            $kangis = trim((string) $request->input('kangisFileNo', ''));
            $newKangis = trim((string) $request->input('NewKANGISFileno', ''));
            $singleFileno = trim((string) $request->input('fileno', ''));

            // If only a single fileno is provided via smart selector, map it to MLS by default
            if ($mls === '' && $kangis === '' && $newKangis === '' && $singleFileno !== '') {
                $mls = $singleFileno;
            }

            // Allow submission without file numbers for testing/optional records
            // if ($mls === '' && $kangis === '' && $newKangis === '') {
            //     throw new \Exception('At least one file number type is required');
            // }
            
            // Create registration number from components (use "0" for null values)
            $serialNo = $request->serialNo ?: '0';
            $pageNo = $request->pageNo ?: '0';
            $volumeNo = $request->volumeNo ?: '0';
            $regNo = $serialNo . '/' . $pageNo . '/' . $volumeNo;
            
            // Get transaction-specific party information (robust to labels and field names)
            $partyData = [];
            $tx = $request->transactionType ? strtolower((string)$request->transactionType) : '';
            
            \Log::info('Processing transaction type:', ['type' => $tx]);
            
            if ($tx && strpos($tx, 'assignment') !== false) {
                $partyData['Assignor'] = $request->input('Assignor') ?? $request->input('trans-assignor-record');
                $partyData['Assignee'] = $request->input('Assignee') ?? $request->input('trans-assignee-record');
            } elseif ($tx && strpos($tx, 'mortgage') !== false) {
                $partyData['Mortgagor'] = $request->input('Mortgagor') ?? $request->input('mortgagor-record');
                $partyData['Mortgagee'] = $request->input('Mortgagee') ?? $request->input('mortgagee-record');
            } elseif ($tx && strpos($tx, 'surrender') !== false) {
                $partyData['Surrenderor'] = $request->input('Surrenderor') ?? $request->input('surrenderor-record');
                $partyData['Surrenderee'] = $request->input('Surrenderee') ?? $request->input('surrenderee-record');
            } elseif ($tx && strpos($tx, 'lease') !== false) {
                $partyData['Lessor'] = $request->input('Lessor') ?? $request->input('lessor-record');
                $partyData['Lessee'] = $request->input('Lessee') ?? $request->input('lessee-record');
            } elseif ($tx) {
                $partyData['Grantor'] = $request->input('Grantor') ?? $request->input('grantor-record');
                $partyData['Grantee'] = $request->input('Grantee') ?? $request->input('grantee-record');
            }

            \Log::info('Party data extracted:', $partyData);

            // Determine title_type based on transaction type or use provided value
            $titleType = $request->input('title_type');
            if (!$titleType) {
                // Auto-determine title_type based on transaction_type
                $titleType = $request->transactionType ? $this->determineTitleType($request->transactionType) : 'Other';
            }

            // NEW: Route CofO-related transactions to CofO table instead of property_records
            $cofoTransactionTypes = [
                'Certificate of Occupancy',
                'ST Certificate of Occupancy',
                'SLTR Certificate of Occupancy',
            ];
            $isCofO = $request->transactionType && in_array($request->transactionType, $cofoTransactionTypes, true);

            if ($isCofO) {
                // Ensure CofO table exists
                if (!Schema::connection('sqlsrv')->hasTable('CofO')) {
                    throw new \Exception('Table CofO does not exist in the database');
                }
                $cofoColumns = Schema::connection('sqlsrv')->getColumnListing('CofO');

                // Map cofo_type
                $cofoTypeMap = [
                    'Certificate of Occupancy' => 'Legacy CofO',
                    'ST Certificate of Occupancy' => 'ST CofO',
                    'SLTR Certificate of Occupancy' => 'SLTR CofO',
                ];
                $cofoType = $cofoTypeMap[$request->transactionType] ?? null;

                // Build full data payload for CofO, then filter by available columns
                $allCofOData = [
                    'np_fileno' => $request->input('np_fileno') ?: null,
                    'mlsFNo' => $mls ?: null,
                    'kangisFileNo' => $kangis ?: null,
                    'NewKANGISFileno' => $newKangis ?: null,
                    'title_type' => $titleType,
                    'transaction_type' => $request->transactionType ?: null,
                    'transaction_date' => $request->transactionDate ?: null,
                    'transaction_time' => $request->regTime ?: '00:00:00',
                    'serialNo' => $serialNo,
                    'pageNo' => $pageNo,
                    'volumeNo' => $volumeNo,
                    'regNo' => $regNo,
                    'instrument_type' => $request->instrumentType ?: $request->transactionType,
                    'period' => $request->period ? (int)$request->period : null,
                    'period_unit' => $request->periodUnit,
                    'Assignor' => $partyData['Assignor'] ?? null,
                    'Assignee' => $partyData['Assignee'] ?? null,
                    'Mortgagor' => $partyData['Mortgagor'] ?? null,
                    'Mortgagee' => $partyData['Mortgagee'] ?? null,
                    'Surrenderor' => $partyData['Surrenderor'] ?? null,
                    'Surrenderee' => $partyData['Surrenderee'] ?? null,
                    'Lessor' => $partyData['Lessor'] ?? null,
                    'Lessee' => $partyData['Lessee'] ?? null,
                    'Grantor' => $partyData['Grantor'] ?? null,
                    'Grantee' => $partyData['Grantee'] ?? null,
                    'property_description' => $request->property_description,
                    'location' => $request->location,
                    'plot_no' => $request->plot_no,
                    'lgsaOrCity' => $request->lgsaOrCity,
                    'layout' => $request->layout,
                    'schedule' => $request->schedule,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'land_use' => $request->landUse,
                    'cofo_type' => $cofoType,
                    // New fields added per request
                    'tp_no' => $request->tp_no,
                    'lpkn_no' => $request->lpkn_no,
                    'approved_plan_no' => $request->approved_plan_no,
                    'plot_size' => $request->plot_size,
                    'date_recommended' => $request->date_recommended,
                    'date_approved' => $request->date_approved,
                    'lease_begins' => $request->lease_begins,
                    'lease_expires' => $request->lease_expires,
                    'metric_sheet' => $request->metric_sheet,
                ];

                // Keep only keys that exist as columns in CofO table
                $filteredCofOData = [];
                foreach ($allCofOData as $key => $value) {
                    if (in_array($key, $cofoColumns, true)) {
                        $filteredCofOData[$key] = $value;
                    }
                }

                \Log::info('CofO data for insertion:', $filteredCofOData);

                // Insert into CofO table
                $id = DB::connection('sqlsrv')->table('CofO')->insertGetId($filteredCofOData);

                // Commit and respond
                DB::connection('sqlsrv')->commit();
                \Log::info('CofO record created successfully:', ['id' => $id]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'CofO record created successfully',
                        'data' => ['id' => $id, 'table' => 'CofO']
                    ], 201);
                }

                return redirect()->route('propertycard.index')->with('success', 'CofO record created successfully');
            }

            // Prepare base data for database insertion
            $data = [
                'mlsFNo' => $mls ?: null,
                'kangisFileNo' => $kangis ?: null,
                'NewKANGISFileno' => $newKangis ?: null,
                'title_type' => $titleType, // Add required title_type field
                'transaction_type' => $request->transactionType ?: null,
                'transaction_date' => $request->transactionDate ?: null,
                'serialNo' => $serialNo,
                'pageNo' => $pageNo,
                'volumeNo' => $volumeNo,
                'regNo' => $regNo,
                'instrument_type' => $request->instrumentType,
                'period' => $request->period ? (int)$request->period : null,
                'period_unit' => $request->periodUnit,
                'property_description' => $request->property_description,
                'plot_no' => $request->plot_no,
            ];

            // Conditionally include optional columns if they exist in the table
            $optionalFields = [
                'location' => $request->location,
                'lgsaOrCity' => $request->lgsaOrCity,
                'layout' => $request->layout,
                'schedule' => $request->schedule,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
                // Registration date and time (use defaults if null)
                'regDate' => $request->regDate ?: now()->toDateString(),
                'regTime' => $request->regTime ?: '00:00:00',
                // New fields added per request
                'tp_no' => $request->tp_no,
                'lpkn_no' => $request->lpkn_no,
                'approved_plan_no' => $request->approved_plan_no,
                'plot_size' => $request->plot_size,
                'date_recommended' => $request->date_recommended,
                'date_approved' => $request->date_approved,
                'lease_begins' => $request->lease_begins,
                'lease_expires' => $request->lease_expires,
                'metric_sheet' => $request->metric_sheet,
            ];

            foreach ($optionalFields as $field => $value) {
                if (Schema::connection('sqlsrv')->hasColumn('property_records', $field)) {
                    $data[$field] = $value;
                }
            }

            // Add party data only for non-null values
            foreach ($partyData as $key => $value) {
                if ($value !== null && $value !== '' && Schema::connection('sqlsrv')->hasColumn('property_records', $key)) {
                    $data[$key] = $value;
                }
            }

            \Log::info('Final data for insertion:', $data);

            // Insert into database
            $id = DB::connection('sqlsrv')->table('property_records')->insertGetId($data);

            // Commit the transaction
            DB::connection('sqlsrv')->commit();

            \Log::info('Property record created successfully:', ['id' => $id]);

            // Check if request expects JSON (AJAX) or normal redirect
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Property record created successfully',
                    'data' => ['id' => $id]
                ], 201);
            }

            // For normal form submissions, redirect with success message
            return redirect()->route('propertycard.index')->with('success', 'Property record created successfully');

        } catch (\Exception $e) {
            // Rollback the transaction
            DB::connection('sqlsrv')->rollback();

            \Log::error('Property Record Creation Failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);

            // Check if request expects JSON (AJAX) or normal redirect
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create property record',
                    'error' => $e->getMessage(),
                    'debug_info' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ]
                ], 500);
            }

            // For normal form submissions, redirect with error message
            return redirect()->back()->with('error', 'Failed to create property record: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update the specified property record in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Modified validation rules for update - make file numbers optional
        $validator = Validator::make($request->all(), [
           
            'transactionType' => 'nullable|string',
            'transactionDate' => 'nullable|date',
            'serialNo' => 'nullable|string',
            'pageNo' => 'nullable|string',
            'volumeNo' => 'nullable|string',
            'instrumentType' => 'nullable|string',
            'period' => 'nullable|numeric',
            'periodUnit' => 'nullable',
            // Party fields
            'Assignor' => 'nullable|string',
            'Assignee' => 'nullable|string',
            'Mortgagor' => 'nullable|string',
            'Mortgagee' => 'nullable|string',
            'Surrenderor' => 'nullable|string',
            'Surrenderee' => 'nullable|string',
            'Lessor' => 'nullable|string',
            'Lessee' => 'nullable|string',
            'Grantor' => 'nullable|string',
            'Grantee' => 'nullable|string',
            // Property details
            'property_description' => 'nullable|string',
            'location' => 'nullable|string',
            'plot_no' => 'nullable|string',
            // New fields
            'lgsaOrCity' => 'nullable|string',
            'layout' => 'nullable|string',
            'schedule' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create registration number from components (use "0" for null values)
            $serialNo = $request->serialNo ?: '0';
            $pageNo = $request->pageNo ?: '0';
            $volumeNo = $request->volumeNo ?: '0';
            $regNo = $serialNo . '/' . $pageNo . '/' . $volumeNo;
            
            // Get existing property record to preserve file numbers
            $existingProperty = DB::connection('sqlsrv')->table('property_records')
                ->where('id', $id)
                ->first();
                
            if (!$existingProperty) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Property record not found'
                ], 404);
            }
            
            // Get transaction-specific party information from edit form
            $partyData = [];
            
            // Add debug output to help diagnose party field issues
            \Log::info('Transaction Type: ' . $request->transactionType);
            \Log::info('Request data: ', $request->all());
            
            switch (strtolower($request->transactionType)) {
                case 'assignment':
                    $partyData['Assignor'] = $request->input('Assignor');
                    $partyData['Assignee'] = $request->input('Assignee');
                    break;
                case 'mortgage':
                    $partyData['Mortgagor'] = $request->input('Mortgagor');
                    $partyData['Mortgagee'] = $request->input('Mortgagee');
                    break;
                case 'surrender':
                    $partyData['Surrenderor'] = $request->input('Surrenderor');
                    $partyData['Surrenderee'] = $request->input('Surrenderee');
                    break;
                case 'sub-lease':
                case 'lease':
                    $partyData['Lessor'] = $request->input('Lessor');
                    $partyData['Lessee'] = $request->input('Lessee');
                    break;
                default:
                    $partyData['Grantor'] = $request->input('Grantor');
                    $partyData['Grantee'] = $request->input('Grantee');
            }
            
            // Log party data for debugging
            \Log::info('Party data to be updated: ', $partyData);

            // Prepare data for database update (keep file numbers; include optional columns only if present)
            $data = [
                'mlsFNo' => $existingProperty->mlsFNo,
                'kangisFileNo' => $existingProperty->kangisFileNo,
                'NewKANGISFileno' => $existingProperty->NewKANGISFileno,
                'transaction_type' => $request->transactionType,
                'transaction_date' => $request->transactionDate,
                'serialNo' => $request->serialNo,
                'pageNo' => $request->pageNo,
                'volumeNo' => $request->volumeNo,
                'regNo' => $regNo,
                'instrument_type' => $request->instrumentType,
                'period' => $request->period,
                'period_unit' => $request->periodUnit,
                'property_description' => $request->property_description,
                'plot_no' => $request->plot_no,
            ];
            if (Schema::hasColumn('property_records', 'lgsaOrCity')) {
                $data['lgsaOrCity'] = $request->lgsaOrCity;
            }
            if (Schema::hasColumn('property_records', 'layout')) {
                $data['layout'] = $request->layout;
            }
            if (Schema::hasColumn('property_records', 'schedule')) {
                $data['schedule'] = $request->schedule;
            }
            if (Schema::hasColumn('property_records', 'updated_by')) {
                $data['updated_by'] = Auth::id();
            }
            if (Schema::hasColumn('property_records', 'updated_at')) {
                $data['updated_at'] = now();
            }

            // Merge party data only if values are not null
            foreach ($partyData as $key => $value) {
                if ($value !== null) {
                    $data[$key] = $value;
                }
            }

            // Update the database record
            DB::connection('sqlsrv')->table('property_records')
                ->where('id', $id)
                ->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Property record updated successfully'
            ]);
        } catch (\Exception $e) {
            // Add more detailed error logging
            \Log::error('Error updating property record: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update property record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified property record from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::connection('sqlsrv')->table('property_records')
                ->where('id', $id)
                ->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Property record deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete property record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified property record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $property = DB::connection('sqlsrv')->table('property_records')
                ->where('id', $id)
                ->first();

            if (!$property) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Property record not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $property
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve property record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for file numbers for property records dropdown
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchFileNumbers(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            // Log the request for debugging
            \Log::info('File number search request:', [
                'search' => $search,
                'page' => $page,
                'method' => $request->method(),
                'all_input' => $request->all()
            ]);

            // Search across multiple sources for file numbers
            $fileNumbers = collect();

            // Search in existing property records
            if (!empty($search)) {
                // Fix: Use proper string comparison instead of complex where clauses
                $propertyRecords = DB::connection('sqlsrv')
                    ->table('property_records')
                    ->select('id', 'mlsFNo as fileno', 'property_description as description', 'plot_no', 'lgsaOrCity as lga', 'location')
                    ->whereRaw("mlsFNo LIKE ?", ["%{$search}%"])
                    ->whereNotNull('mlsFNo')
                    ->whereRaw("mlsFNo != ''")
                    ->limit($perPage)
                    ->get();

                foreach ($propertyRecords as $record) {
                    $fileNumbers->push([
                        'id' => 'property_' . $record->id,
                        'fileno' => $record->fileno,
                        'description' => $record->description,
                        'plot_no' => $record->plot_no,
                        'lga' => $record->lga,
                        'location' => $record->location,
                        'source' => 'property_records'
                    ]);
                }

                // Search KANGIS file numbers
                $kangisRecords = DB::connection('sqlsrv')
                    ->table('property_records')
                    ->select('id', 'kangisFileNo as fileno', 'property_description as description', 'plot_no', 'lgsaOrCity as lga', 'location')
                    ->whereRaw("kangisFileNo LIKE ?", ["%{$search}%"])
                    ->whereNotNull('kangisFileNo')
                    ->whereRaw("kangisFileNo != ''")
                    ->limit($perPage)
                    ->get();

                foreach ($kangisRecords as $record) {
                    $fileNumbers->push([
                        'id' => 'kangis_' . $record->id,
                        'fileno' => $record->fileno,
                        'description' => $record->description,
                        'plot_no' => $record->plot_no,
                        'lga' => $record->lga,
                        'location' => $record->location,
                        'source' => 'property_records'
                    ]);
                }

                // Search New KANGIS file numbers
                $newKangisRecords = DB::connection('sqlsrv')
                    ->table('property_records')
                    ->select('id', 'NewKANGISFileno as fileno', 'property_description as description', 'plot_no', 'lgsaOrCity as lga', 'location')
                    ->whereRaw("NewKANGISFileno LIKE ?", ["%{$search}%"])
                    ->whereNotNull('NewKANGISFileno')
                    ->whereRaw("NewKANGISFileno != ''")
                    ->limit($perPage)
                    ->get();

                foreach ($newKangisRecords as $record) {
                    $fileNumbers->push([
                        'id' => 'newkangis_' . $record->id,
                        'fileno' => $record->fileno,
                        'description' => $record->description,
                        'plot_no' => $record->plot_no,
                        'lga' => $record->lga,
                        'location' => $record->location,
                        'source' => 'property_records'
                    ]);
                }

                // Search in applications table if it exists
                try {
                    $applications = DB::connection('sqlsrv')
                        ->table('dbo.mother_applications')
                        ->select('id', 'fileno', 'plot_no', 'lga_name as lga', 'layout_name as location')
                        ->whereRaw("fileno LIKE ?", ["%{$search}%"])
                        ->whereNotNull('fileno')
                        ->whereRaw("fileno != ''")
                        ->limit($perPage)
                        ->get();

                    foreach ($applications as $app) {
                        $fileNumbers->push([
                            'id' => 'app_' . $app->id,
                            'fileno' => $app->fileno,
                            'description' => 'Application Record',
                            'plot_no' => $app->plot_no,
                            'lga' => $app->lga,
                            'location' => $app->location,
                            'source' => 'applications'
                        ]);
                    }
                } catch (\Exception $e) {
                    // Applications table might not exist or be accessible
                }
            } else {
                // If no search term, return recent file numbers or sample data
                try {
                    $recentRecords = DB::connection('sqlsrv')
                        ->table('property_records')
                        ->select('id', 'mlsFNo as fileno', 'property_description as description', 'plot_no', 'lgsaOrCity as lga', 'location')
                        ->whereNotNull('mlsFNo')
                        ->whereRaw("mlsFNo != ''")
                        ->orderBy('created_at', 'desc')
                        ->limit($perPage)
                        ->get();

                    foreach ($recentRecords as $record) {
                        $fileNumbers->push([
                            'id' => 'recent_' . $record->id,
                            'fileno' => $record->fileno,
                            'description' => $record->description,
                            'plot_no' => $record->plot_no,
                            'lga' => $record->lga,
                            'location' => $record->location,
                            'source' => 'property_records'
                        ]);
                    }
                } catch (\Exception $e) {
                    // If database query fails, provide sample data for testing
                    $sampleData = [
                        [
                            'id' => 'sample_1',
                            'fileno' => 'COM-2023-001',
                            'description' => 'Commercial Property',
                            'plot_no' => '123',
                            'lga' => 'Kano Municipal',
                            'location' => 'Sabon Gari',
                            'source' => 'sample'
                        ],
                        [
                            'id' => 'sample_2',
                            'fileno' => 'RES-2023-002',
                            'description' => 'Residential Property',
                            'plot_no' => '456',
                            'lga' => 'Fagge',
                            'location' => 'Fagge Layout',
                            'source' => 'sample'
                        ],
                        [
                            'id' => 'sample_3',
                            'fileno' => 'KNML 00001',
                            'description' => 'KANGIS Property',
                            'plot_no' => '789',
                            'lga' => 'Gwale',
                            'location' => 'Gwale District',
                            'source' => 'sample'
                        ]
                    ];
                    
                    foreach ($sampleData as $sample) {
                        $fileNumbers->push($sample);
                    }
                }
            }

            // Remove duplicates based on fileno
            $uniqueFileNumbers = $fileNumbers->unique('fileno')->values();

            // Paginate results
            $total = $uniqueFileNumbers->count();
            $results = $uniqueFileNumbers->slice($offset, $perPage)->values();

            return response()->json([
                'success' => true,
                'file_numbers' => $results,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'more' => ($offset + $perPage) < $total
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching file numbers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Determine title type based on transaction type
     *
     * @param string $transactionType
     * @return string
     */
    private function determineTitleType($transactionType)
    {
        $titleTypeMap = [
            'Certificate of Occupancy' => 'C of O',
            'ST Certificate of Occupancy' => 'ST C of O',
            'SLTR Certificate of Occupancy' => 'SLTR C of O',
            'Customary Right of Occupancy' => 'Customary R of O',
            'Deed of Assignment' => 'Assignment',
            'ST Assignment' => 'ST Assignment',
            'Deed of Mortgage' => 'Mortgage',
            'Tripartite Mortgage' => 'Tripartite Mortgage',
            'Deed of Lease' => 'Lease',
            'Deed of Sub Lease' => 'Sub Lease',
            'Deed of Sub Under Lease' => 'Sub Under Lease',
            'Indenture of Lease' => 'Indenture Lease',
            'Quarry Lease' => 'Quarry Lease',
            'Private Lease' => 'Private Lease',
            'Building Lease' => 'Building Lease',
            'Tenancy Agreement' => 'Tenancy',
            'Deed of Surrender' => 'Surrender',
            'Deed of Transfer' => 'Transfer',
            'Deed of Gift' => 'Gift',
            'Power of Attorney' => 'Power of Attorney',
            'Irrevocable Power of Attorney' => 'Irrevocable POA',
            'Deed of Release' => 'Release',
            'Letter of Administration' => 'Letter of Admin',
            'Certificate of Purchase' => 'Certificate of Purchase',
            'Deed of Variation' => 'Variation',
            'Vesting Assent' => 'Vesting Assent',
            'Court Judgement' => 'Court Judgement',
            'Exchange of Letters' => 'Exchange of Letters',
            'Revocation of Power of Attorney' => 'Revocation POA',
            'Deed of Convenyence' => 'Convenyence',
            'Memorandom of Agreement' => 'MOU',
            'Deed of Partition' => 'Partition',
            'Non-European Occupational Lease' => 'Non-European Lease',
            'Deed of Revocation' => 'Revocation',
            'Deed of Reconveyance' => 'Reconveyance',
            'Customary Inhertitance' => 'Customary Inheritance',
            'Deed of Rectification' => 'Rectification',
            'Memorandum of Loss' => 'Memorandum of Loss',
            'Vesting Deed' => 'Vesting Deed',
            'ST Fragmentation' => 'ST Fragmentation',
        ];

        return $titleTypeMap[$transactionType] ?? 'Other';
    }

    /**
     * Check for existing property records for a file number
     * Used by Edit File Index page to determine Add vs Update button
     *
     * @param  string  $fileNumber
     * @return \Illuminate\Http\Response
     */
    public function checkExistingRecords($fileNumber)
    {
        try {
            \Log::info('Checking existing property records for file number: ' . $fileNumber);

            // Parse file number to check all possible columns
            $mlsFNo = '';
            $kangisFileNo = '';
            $newKangisFileNo = '';
            
            $fileNumber = trim($fileNumber);
            
            // Use same parsing logic as storeFromIndexing
            if (preg_match('/^(MLKN|KNML)\s*\d+/i', $fileNumber)) {
                $kangisFileNo = $fileNumber;
            } elseif (preg_match('/^KN\s*\d+$/i', $fileNumber)) {
                $newKangisFileNo = $fileNumber;
            } elseif (preg_match('/^(RES|COM|IND|AGR|CON)-/i', $fileNumber)) {
                $mlsFNo = $fileNumber;
            } elseif (preg_match('/^(MLS|MLSF)[\s-]/i', $fileNumber)) {
                $mlsFNo = $fileNumber;
            } elseif (preg_match('/^ST-/i', $fileNumber)) {
                $mlsFNo = $fileNumber;
            } elseif (preg_match('/^KANGIS[\/-]\d{4}/i', $fileNumber)) {
                $newKangisFileNo = $fileNumber;
            } elseif (preg_match('/^[A-Z]{2,}-[A-Z]{2,}-\d+/i', $fileNumber)) {
                $kangisFileNo = $fileNumber;
            } else {
                $mlsFNo = $fileNumber;
            }

            // Search for existing property records
            $existingRecords = DB::connection('sqlsrv')->table('property_records')
                ->where(function($query) use ($mlsFNo, $kangisFileNo, $newKangisFileNo) {
                    if ($mlsFNo) $query->orWhere('mlsfNo', $mlsFNo);
                    if ($kangisFileNo) $query->orWhere('kangisFileNo', $kangisFileNo);
                    if ($newKangisFileNo) $query->orWhere('NewKANGISFileno', $newKangisFileNo);
                })
                ->orderBy('created_at', 'asc')
                ->get();

            \Log::info('Found property records: ' . $existingRecords->count());

            return response()->json([
                'success' => true,
                'records' => $existingRecords,
                'count' => $existingRecords->count(),
                'file_number_parsed' => [
                    'mlsfNo' => $mlsFNo,
                    'kangisFileNo' => $kangisFileNo, 
                    'newKangisFileNo' => $newKangisFileNo
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error checking existing property records: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error checking existing records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store property record transactions from file indexing
     * Also creates/updates fileNumber table entry
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeFromIndexing(Request $request)
    {
        try {
            \Log::info('=== Property Record from File Indexing START ===');
            \Log::info('Request Data:', $request->all());

            // Validate the request - file_indexing_id is now optional
            $validator = Validator::make($request->all(), [
                'file_number' => 'required|string|max:255',
                'transactions' => 'required|array|min:1',
                'transactions.*.transaction_type' => 'required|string',
                'transactions.*.transaction_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                \Log::error('Validation Failed:', [
                    'errors' => $validator->errors()->toArray(),
                    'input' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fileNumber = $request->file_number;
            $transactions = $request->transactions;
            
            \Log::info('Validation passed. Processing file number: ' . $fileNumber);

            // Parse file number to determine which format it is
            $mlsFNo = '';
            $kangisFileNo = '';
            $newKangisFileNo = '';

            // Clean up file number (remove extra spaces)
            $fileNumber = trim($fileNumber);
            
            \Log::info('Parsing file number format', ['file_number' => $fileNumber]);

            // Check for different file number formats based on ACTUAL database patterns
            if (preg_match('/^(MLKN|KNML)\s*\d+/i', $fileNumber)) {
                // Old KANGIS format: MLKN 01084, MLKN 01640, KNML 08791
                $kangisFileNo = $fileNumber;
                \Log::info('Identified as old KANGIS format (MLKN/KNML)', ['kangisFileNo' => $kangisFileNo]);
            } elseif (preg_match('/^KN\s*\d+$/i', $fileNumber)) {
                // Simple KN format: KN 12, KN122 (goes to NewKANGISFileNo)
                $newKangisFileNo = $fileNumber;
                \Log::info('Identified as simple KN format', ['newKangisFileNo' => $newKangisFileNo]);
            } elseif (preg_match('/^(RES|COM|IND|AGR|CON)-/i', $fileNumber)) {
                // Modern land use formats: RES-2025-9, COM-2016-318, CON-AGR-2025-10, IND-2025-6
                // These go to mlsfNo column (based on actual database)
                $mlsFNo = $fileNumber;
                \Log::info('Identified as modern land use format (goes to mlsfNo)', ['mlsFNo' => $mlsFNo]);
            } elseif (preg_match('/^(MLS|MLSF)[\s-]/i', $fileNumber)) {
                // Actual MLS/MLSF format with space or hyphen: MLS 123, MLSF-456
                $mlsFNo = $fileNumber;
                \Log::info('Identified as traditional MLS/MLSF format', ['mlsFNo' => $mlsFNo]);
            } elseif (preg_match('/^ST-/i', $fileNumber)) {
                // New ST format: ST-{LAND_USE}-{YEAR}-{SERIAL}
                $mlsFNo = $fileNumber;
                \Log::info('Identified as ST format (goes to mlsfNo)', ['mlsFNo' => $mlsFNo]);
            } elseif (preg_match('/^KANGIS[\/-]\d{4}/i', $fileNumber)) {
                // NewKANGIS format with year: KANGIS/2025/123, KANGIS-2025-456
                $newKangisFileNo = $fileNumber;
                \Log::info('Identified as NewKANGIS format with year', ['newKangisFileNo' => $newKangisFileNo]);
            } elseif (preg_match('/^[A-Z]{2,}-[A-Z]{2,}-\d+/i', $fileNumber)) {
                // Legacy hyphenated formats: AG-RC-81-30 (goes to kangisFileNo)
                $kangisFileNo = $fileNumber;
                \Log::info('Identified as legacy hyphenated format', ['kangisFileNo' => $kangisFileNo]);
            } else {
                // Default to mlsfNo for any unrecognized format (modern default)
                $mlsFNo = $fileNumber;
                \Log::info('Using mlsfNo (modern fallback)', ['mlsFNo' => $mlsFNo]);
            }

            // Check for existing fileNumber records only (always proceed with indexing and property records)
            $fileNumberExists = null;
            
            // Check if file number already exists in fileNumber table
            if ($mlsFNo || $kangisFileNo || $newKangisFileNo) {
                $fileNumberExists = DB::connection('sqlsrv')->table('fileNumber')
                    ->where(function($query) use ($mlsFNo, $kangisFileNo, $newKangisFileNo) {
                        if ($mlsFNo) $query->orWhere('mlsfNo', $mlsFNo);
                        if ($kangisFileNo) $query->orWhere('kangisFileNo', $kangisFileNo);
                        if ($newKangisFileNo) $query->orWhere('NewKANGISFileNo', $newKangisFileNo);
                    })
                    ->first();
            }

            // Only insert to fileNumber table if it doesn't already exist
            if (!$fileNumberExists) {
                // Insert new file number record using data from request
                $fileNumberInserted = DB::connection('sqlsrv')->table('fileNumber')->insert([
                    'mlsfNo' => $mlsFNo ?: null,
                    'kangisFileNo' => $kangisFileNo ?: null,
                    'NewKANGISFileNo' => $newKangisFileNo ?: null,
                    'FileName' => $request->file_title ?? null,
                    'location' => $request->property_description ?? null,
                    'plot_no' => $request->plot_no ?? null,
                    'tp_no' => $request->tp_no ?? null,
                    'type' => 'indexing',
                    'SOURCE' => 'indexing',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                \Log::info('fileNumber insert result: ' . ($fileNumberInserted ? 'SUCCESS' : 'FAILED'), [
                    'mlsfNo' => $mlsFNo,
                    'kangisFileNo' => $kangisFileNo,
                    'newKangisFileNo' => $newKangisFileNo,
                    'file_title' => $request->file_title
                ]);

                \Log::info('Created new fileNumber record', [
                    'mlsfNo' => $mlsFNo,
                    'kangisFileNo' => $kangisFileNo,
                    'NewKANGISFileNo' => $newKangisFileNo
                ]);
            } else {
                \Log::info('Skipping fileNumber insert - record already exists', [
                    'file_number' => $fileNumber,
                    'existing_id' => $fileNumberExists->id ?? 'unknown'
                ]);
            }

            // Always proceed to create indexing and property records regardless of fileNumber table status

            // Create property records for each transaction
            $createdRecords = [];
            foreach ($transactions as $transaction) {
                // Prepare data for property_records table using request data
                // Map to actual column names in the database
                $propertyData = [
                    'mlsfNo' => $mlsFNo ?: null,
                    'kangisFileNo' => $kangisFileNo ?: null,
                    'NewKANGISFileno' => $newKangisFileNo ?: null,
                    'transaction_type' => $transaction['transaction_type'] ?? null,
                    'instrument_type' => $transaction['transaction_type'] ?? null, // Same as transaction_type
                    'transaction_date' => $transaction['transaction_date'] ?? null,
                    'regNo' => trim(implode('/', array_filter([
                        $transaction['serial_no'] ?? '',
                        $transaction['page_no'] ?? '',
                        $transaction['volume_no'] ?? ''
                    ]))) ?: null,
                    'serialNo' => $transaction['serial_no'] ?? null,
                    'pageNo' => $transaction['page_no'] ?? null,
                    'volumeNo' => $transaction['volume_no'] ?? null,
                    // Note: regDate, regTime, landUse, periodUnit columns don't exist in property_records
                    // Using period and period_unit instead
                    'period' => $transaction['period'] ?? null,
                    'period_unit' => $transaction['period_unit'] ?? null,
                    'property_description' => $request->property_description ?? null,
                    'location' => $request->property_description ?? null,
                    'districtName' => $request->district ?? null,
                    'plot_no' => $request->plot_no ?? null,
                    'lgsaOrCity' => $request->lga ?? null,
                    'tp_no' => $request->tp_no ?? null,
                    'lpkn_no' => $request->lpkn_no ?? null,
                    'title_type' => $this->determineTitleType($transaction['transaction_type'] ?? ''),
                    'source' => 'indexing',
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // Add party fields based on transaction type
                $partyFields = $this->getPartyFieldsFromTransaction($transaction);
                $propertyData = array_merge($propertyData, $partyFields);

                // Insert into property_records
                $propertyRecordId = DB::connection('sqlsrv')->table('property_records')
                    ->insertGetId($propertyData);

                $createdRecords[] = $propertyRecordId;

                \Log::info('Created property record', [
                    'id' => $propertyRecordId,
                    'transaction_type' => $transaction['transaction_type']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Property transaction details saved successfully!',
                'data' => [
                    'property_record_ids' => $createdRecords,
                    'transaction_count' => count($createdRecords)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error storing property record from indexing:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save transaction details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract party fields from transaction data
     */
    private function getPartyFieldsFromTransaction($transaction)
    {
        $partyFields = [];
        $transactionType = $transaction['transaction_type'] ?? '';
        $firstParty = $transaction['first_party'] ?? '';
        $secondParty = $transaction['second_party'] ?? '';

        // Map transaction types to party field names
        $partyMapping = [
            'Deed of Assignment' => ['Assignor', 'Assignee'],
            'ST Assignment' => ['Assignor', 'Assignee'],
            'Deed of Mortgage' => ['Mortgagor', 'Mortgagee'],
            'Tripartite Mortgage' => ['Mortgagor', 'Mortgagee'],
            'Deed of Surrender' => ['Surrenderor', 'Surrenderee'],
            'Deed of Sub Lease' => ['Lessor', 'Lessee'],
            'Deed of Sub Under Lease' => ['Lessor', 'Lessee'],
            'Indenture of Lease' => ['Lessor', 'Lessee'],
            'Quarry Lease' => ['Lessor', 'Lessee'],
            'Private Lease' => ['Lessor', 'Lessee'],
            'Building Lease' => ['Lessor', 'Lessee'],
            'Deed of Lease' => ['Lessor', 'Lessee'],
            'Tenancy Agreement' => ['Landlord', 'Tenant'],
            'Deed of Release' => ['Releasor', 'Releasee'],
            'Deed of Transfer' => ['Transferor', 'Transferee'],
            'Deed of Gift' => ['Donor', 'Donee'],
            'Letter of Administration' => ['Administrator', 'Beneficiary'],
            'Certificate of Purchase' => ['Vendor', 'Purchaser']
        ];

        if (isset($partyMapping[$transactionType])) {
            $partyFields[$partyMapping[$transactionType][0]] = $firstParty;
            $partyFields[$partyMapping[$transactionType][1]] = $secondParty;
        } else {
            // Default to Grantor/Grantee
            $partyFields['Grantor'] = $firstParty;
            $partyFields['Grantee'] = $secondParty;
        }

        return $partyFields;
    }
}