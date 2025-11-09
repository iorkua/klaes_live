<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PrimaryActionsController extends Controller
{
    // Fetch application by ID (for payment modal)
    public function show($id)
    {
        // Use the DB facade to query the 'mother_applications' table (adjust table name if needed)
        $application = DB::connection('sqlsrv')->table('mother_applications')
            ->where('id', $id)
            ->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }
        return response()->json($application);
    }


    public function store(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'application_id' => 'required|integer',
                'fileno' => 'required|string|max:255',
                // Survey personnel information
                'survey_by' => 'required|string|max:255',
                'survey_by_date' => 'required|date',
                'drawn_by' => 'required|string|max:255',
                'drawn_by_date' => 'required|date',
                'checked_by' => 'required|string|max:255',
                'checked_by_date' => 'required|date',
                'approved_by' => 'required|string|max:255',
                'approved_by_date' => 'required|date',
                // Property Identification
                'plot_no' => 'nullable|string|max:255',
                'block_no' => 'nullable|string|max:255',
                'approved_plan_no' => 'nullable|string|max:255',
                'tp_plan_no' => 'nullable|string|max:255',
                // Beacon Control Information
                'beacon_control_name' => 'nullable|string|max:255',
                'Control_Beacon_Coordinate_X' => 'nullable|string|max:255',
                'Control_Beacon_Coordinate_Y' => 'nullable|string|max:255',
                // Sheet Information
                'Metric_Sheet_Index' => 'nullable|string|max:255',
                'Metric_Sheet_No' => 'nullable|string|max:255',
                'Imperial_Sheet' => 'nullable|string|max:255',
                'Imperial_Sheet_No' => 'nullable|string|max:255',
                // Location Information
                'layout_name' => 'nullable|string|max:255',
                'district_name' => 'nullable|string|max:255',
                'lga_name' => 'nullable|string|max:255',
            ]);

            // Insert the data into the database
            DB::connection('sqlsrv')->table('surveyRecord')->insert($validatedData);

            // Return JSON response for AJAX
            return response()->json([
                'success' => true,
                'message' => 'Survey submitted successfully!'
            ]);
        } catch (\Exception $e) {
            // Return JSON error response
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    public function storeDeeds(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'application_id' => 'required|integer',
                'fileno' => 'required|string|max:255',
                'serial_no' => 'required|string|max:255',
                'page_no' => 'required|string|max:255',
                'volume_no' => 'required|string|max:255',
                'deeds_time' => 'nullable|string',
                'deeds_date' => 'nullable|date',
            ]);

            // Get the mother application
            $motherApplication = DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $request->application_id)
                ->first();

            if (!$motherApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mother application not found!'
                ], 404);
            }

            // Check if CofO record already exists for this file number
            $existingCofo = DB::connection('sqlsrv')->table('Cofo')
                ->where('fileNo', $validatedData['fileno'])
                ->first();

            if ($existingCofo) {
                // Update existing CofO record
                DB::connection('sqlsrv')->table('Cofo')
                    ->where('fileNo', $validatedData['fileno'])
                    ->update([
                        'oldTitleSerialNo' => $validatedData['serial_no'],
                        'oldTitlePageNo' => $validatedData['page_no'],
                        'oldTitleVolumeNo' => $validatedData['volume_no'],
                        'deedsTime' => $validatedData['deeds_time'],
                        'deedsDate' => $validatedData['deeds_date'],
                        'updated_at' => now()
                    ]);
            } else {
                // Insert new CofO record
                DB::connection('sqlsrv')->table('Cofo')->insert([
                    'fileNo' => $validatedData['fileno'],
                    'oldTitleSerialNo' => $validatedData['serial_no'],
                    'oldTitlePageNo' => $validatedData['page_no'],
                    'oldTitleVolumeNo' => $validatedData['volume_no'],
                    'deedsTime' => $validatedData['deeds_time'],
                    'deedsDate' => $validatedData['deeds_date'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Also store in landAdministration table for backward compatibility
            $landAdminData = [
                'application_id' => $validatedData['application_id'],
                'serial_no' => $validatedData['serial_no'],
                'page_no' => $validatedData['page_no'],
                'volume_no' => $validatedData['volume_no'],
                'deeds_time' => $validatedData['deeds_time'],
                'deeds_date' => $validatedData['deeds_date'],
                'Applicant_Name' => $motherApplication->owner_fullname ?? ''
            ];

            // Check if landAdministration record exists
            $existingLandAdmin = DB::connection('sqlsrv')->table('landAdministration')
                ->where('application_id', $validatedData['application_id'])
                ->first();

            if ($existingLandAdmin) {
                DB::connection('sqlsrv')->table('landAdministration')
                    ->where('application_id', $validatedData['application_id'])
                    ->update($landAdminData);
            } else {
                DB::connection('sqlsrv')->table('landAdministration')->insert($landAdminData);
            }

            // Return JSON response for AJAX
            return response()->json([
                'success' => true,
                'message' => 'CofO Registration Particulars saved successfully!'
            ]);
        } catch (\Exception $e) {
            // Return JSON error response
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update planning recommendation status for a mother application
     */
    public function updatePlanningRecommendation(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'application_id' => 'required|integer',
            'status' => 'required|string|in:approve,decline',
            'approval_date' => 'required|date',
            'comments' => 'nullable|string',
        ]);

        try {
            // Map 'approve/decline' to database values
            $status = ($validatedData['status'] === 'approve') ? 'Approved' : 'Declined';

            // Update the mother application record
            $updated = DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $validatedData['application_id'])
                ->update([
                    'planning_recommendation_status' => $status,
                    'planning_approval_date' => $validatedData['approval_date'],
                    'recomm_comments' => $validatedData['comments'] ?? null,
                    'updated_at' => now()
                ]);

            if ($updated) {
                // Handle both AJAX and standard form requests
                if ($request->expectsJson()) {
                    return response()->json(['success' => true]);
                } else {
                    return redirect()->back()->with('success', 'Planning recommendation updated successfully!');
                }
            } else {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Failed to update record or record not found']);
                } else {
                    return redirect()->back()->with('error', 'Failed to update record or record not found.');
                }
            }
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            } else {
                return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
            }
        }
    }

    /**
     * Update director's approval status for a mother application
     */
    public function updateDirectorApproval(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'application_id' => 'required|integer',
            'status' => 'required|string|in:approve,decline',
            'approval_date' => 'required|date',
            'comments' => 'nullable|string',
        ]);

        try {
            // Map 'approve/decline' to database values
            $status = ($validatedData['status'] === 'approve') ? 'Approved' : 'Declined';

            // Get the mother application details
            $motherApplication = DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $validatedData['application_id'])
                ->first();

            if (!$motherApplication) {
                return response()->json(['success' => false, 'message' => 'Application not found']);
            }

            // Update the mother application record
            $updated = DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $validatedData['application_id'])
                ->update([
                    'application_status' => $status,
                    'approval_date' => $validatedData['approval_date'],
                    'director_comments' => $validatedData['comments'] ?? null,
                    'updated_at' => now()
                ]);

            // If Director Approval is granted, automatically submit for instrument registration
            if ($updated && $status === 'Approved') {
                $this->submitForInstrumentRegistration($motherApplication);
            }

            if ($updated) {
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to update record or record not found']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit primary application details for instrument registration
     * Uses logic from InstrumentRegistrationController
     */
    private function submitForInstrumentRegistration($motherApplication)
    {
        try {
            // Generate next serial number for registration
            $serialData = $this->getNextSerialNumber();
            $stmReference = $this->generateSTMReference();

            // Build mother applicant name properly for ST Fragmentation Grantee
            $motherApplicantParts = [];
            if (!empty($motherApplication->applicant_title)) $motherApplicantParts[] = $motherApplication->applicant_title;
            if (!empty($motherApplication->first_name)) $motherApplicantParts[] = $motherApplication->first_name;
            if (!empty($motherApplication->middle_name)) $motherApplicantParts[] = $motherApplication->middle_name;
            if (!empty($motherApplication->surname)) $motherApplicantParts[] = $motherApplication->surname;
            if (!empty($motherApplication->corporate_name)) $motherApplicantParts[] = $motherApplication->corporate_name;
            if (!empty($motherApplication->rc_number)) $motherApplicantParts[] = $motherApplication->rc_number;
            if (!empty($motherApplication->multiple_owners_names)) $motherApplicantParts[] = $motherApplication->multiple_owners_names;
            $motherApplicantName = implode(' ', $motherApplicantParts) ?: ($motherApplication->owner_fullname ?? 'N/A');

            // Prepare registration data for ST Fragmentation
            $registrationData = [
                'particularsRegistrationNumber' => '0/0/0', // ST Fragmentation always uses 0/0/0
                'STM_Ref' => $stmReference,
                'instrument_type' => 'ST Fragmentation',
                'Grantor' => 'Kano State Government', // As specified in requirements
                'Grantee' => $motherApplicantName, // Use properly built mother applicant name
                'instrumentDate' => now(),
                'deeds_date' => now(),
                'deeds_time' => now()->format('H:i:s'),
                'serial_no' => 0, // ST Fragmentation uses 0
                'page_no' => 0, // ST Fragmentation uses 0
                'volume_no' => 0, // ST Fragmentation uses 0
                'status' => 'registered',
                'parent_fileNo' => $motherApplication->fileno, // fileno from mother_applications
                'fileNo' => $motherApplication->np_fileno ?? $motherApplication->fileno, // np_fileno from mother_applications
                'StFileNo' => $motherApplication->np_fileno ?? $motherApplication->fileno,
                'MLSFileNo' => $motherApplication->fileno,
                'lga' => $motherApplication->property_lga ?? '',
                'district' => $motherApplication->property_district ?? '',
                'size' => $motherApplication->plot_size ?? '',
                'plotNumber' => $motherApplication->property_plot_no ?? '',
                'propertyDescription' => $this->buildPropertyDescription($motherApplication),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Insert into registered_instruments table
            DB::connection('sqlsrv')->table('registered_instruments')->insert($registrationData);

            Log::info('ST Fragmentation automatically registered for approved application', [
                'application_id' => $motherApplication->id,
                'fileno' => $motherApplication->fileno,
                'np_fileno' => $motherApplication->np_fileno ?? 'N/A',
                'stm_ref' => $stmReference
            ]);

        } catch (\Exception $e) {
            Log::error('Error submitting for instrument registration', [
                'application_id' => $motherApplication->id,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get next serial number for instrument registration
     */
    private function getNextSerialNumber()
    {
        try {
            $latest = DB::connection('sqlsrv')->table('registered_instruments')
                ->select('volume_no', 'page_no', 'serial_no')
                ->orderBy('volume_no', 'desc')
                ->orderBy('page_no', 'desc')
                ->first();
            
            if (!$latest) {
                return [
                    'serial_no' => 1,
                    'page_no' => 1,
                    'volume_no' => 1,
                    'deeds_serial_no' => '1/1/1'
                ];
            }
            
            $volumeNo = $latest->volume_no; 
            $pageNo = $latest->page_no;
            $serialNo = $latest->serial_no;
            
            if ($pageNo >= 100) {
                $volumeNo++;
                $pageNo = 1;
                $serialNo = 1;
            } else {
                $pageNo++;
                $serialNo++;
            }
            
            $deedsSerialNo = "$serialNo/$pageNo/$volumeNo";
            
            return [
                'serial_no' => $serialNo,
                'page_no' => $pageNo,
                'volume_no' => $volumeNo,
                'deeds_serial_no' => $deedsSerialNo
            ];
        } catch (\Exception $e) {
            Log::error('Error generating next serial number', [
                'exception' => $e->getMessage()
            ]);
            return [
                'serial_no' => 1,
                'page_no' => 1,
                'volume_no' => 1,
                'deeds_serial_no' => '1/1/1'
            ];
        }
    }

    /**
     * Generate STM reference number
     */
    private function generateSTMReference()
    {
        $year = date('Y');
        $latestRef = DB::connection('sqlsrv')->table('registered_instruments')
            ->where('STM_Ref', 'like', "STM-$year-%")
            ->orderBy('id', 'desc')
            ->value('STM_Ref');
        
        if ($latestRef) {
            $matches = [];
            if (preg_match('/STM-\\d{4}-(\\d{4})/', $latestRef, $matches)) {
                $sequence = (int)$matches[1] + 1;
            } else {
                $sequence = 1;
            }
        } else {
            $sequence = 1;
        }
        
        return "STM-{$year}-" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Build property description from mother application details
     */
    private function buildPropertyDescription($motherApplication)
    {
        $propertyParts = [];
        
        if (!empty($motherApplication->property_house_no)) {
            $propertyParts[] = 'House No: ' . $motherApplication->property_house_no;
        }
        if (!empty($motherApplication->property_plot_no)) {
            $propertyParts[] = 'Plot No: ' . $motherApplication->property_plot_no;
        }
        if (!empty($motherApplication->property_street_name)) {
            $propertyParts[] = $motherApplication->property_street_name;
        }
        if (!empty($motherApplication->property_district)) {
            $propertyParts[] = $motherApplication->property_district;
        }
        if (!empty($motherApplication->property_lga)) {
            $propertyParts[] = $motherApplication->property_lga;
        }
        
        $propertyDescription = implode(', ', $propertyParts);
        if (empty($propertyDescription)) {
            $propertyDescription = 'Property details not available';
        }
        
        return $propertyDescription;
    }

    /**
     * Get conveyance data for an application
     */
    public function getConveyance($applicationId)
    {
        try {
            // Query the buyer_list table and join with st_unit_measurements using proper relationship
            // Use DISTINCT to avoid duplicate records when multiple measurements exist for same buyer
            $records = DB::connection('sqlsrv')
                ->table('buyer_list as bl')
                ->leftJoin('st_unit_measurements as sum', function($join) {
                    $join->on('bl.id', '=', 'sum.buyer_id')
                         ->on('bl.application_id', '=', 'sum.application_id');
                })
                ->where('bl.application_id', $applicationId)
                ->select('bl.id', 'bl.buyer_title', 'bl.buyer_name', 'bl.unit_no', 'bl.unit_measurement_id', 'sum.measurement')
                ->distinct()
                ->get()
                ->toArray();

            return response()->json([
                'success' => true,
                'records' => $records
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving buyers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update conveyance data for an application
     */
    public function updateConveyance(Request $request)
    {
        try {
            // Always extract application_id from the request (works for both JSON and FormData)
            $applicationId = $request->input('application_id');
            if (!$applicationId) {
                // Try to get from $_POST directly (for some edge cases)
                $applicationId = $request->get('application_id');
            }

            // Fallback: If 'records' is not an array, try to parse it from the request
            $records = $request->input('records');
            if (!is_array($records)) {
                // Try to parse from JSON if sent as a string
                $records = json_decode($request->input('records'), true);
            }
            // If still not an array, try to build from form data (for multipart/form-data)
            if (!is_array($records)) {
                $records = [];
                foreach ($request->all() as $key => $value) {
                    if (preg_match('/^records\[(\d+)\]\[(\w+)\]$/', $key, $matches)) {
                        $index = $matches[1];
                        $field = $matches[2];
                        $records[$index][$field] = $value;
                    }
                }
                // Re-index array
                $records = array_values($records);
            }

            // Now validate
            $validated = $request->validate([
                // Use extracted application_id for validation
                'application_id' => 'required|integer',
                // Remove 'records' from validation here, validate manually below
            ]);

            // Manual validation for records array
            if (!is_array($records) || count($records) < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one buyer record is required.',
                    'errors' => ['records' => ['At least one buyer record is required.']]
                ], 422);
            }
            foreach ($records as $i => $record) {
                if (empty($record['buyerName'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Buyer name is required for all buyers.",
                        'errors' => ["records.$i.buyerName" => ['Buyer name is required.']]
                    ], 422);
                }
                if (empty($record['sectionNo'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Unit number is required for all buyers.",
                        'errors' => ["records.$i.sectionNo" => ['Unit number is required.']]
                    ], 422);
                }
            }

            // Use the extracted application_id for all logic
            $applicationId = $applicationId ?? $validated['application_id'];

            // Check if the application exists and get its status
            $application = DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $applicationId)
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found.'
                ], 404);
            }

            // Check if both application status and planning recommendation are approved
            if ($application->application_status == 'Approved' && $application->planning_recommendation_status == 'Approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add buyers - Both Application Status and Planning Recommendation have been approved. No further modifications are allowed.'
                ], 403);
            }

            // Process each record
            foreach ($records as $record) {
                // Check if this buyer already exists (by buyer name and unit no)
                $existing = DB::connection('sqlsrv')
                    ->table('buyer_list')
                    ->where('application_id', $applicationId)
                    ->where('buyer_name', $record['buyerName'])
                    ->where('unit_no', $record['sectionNo'])
                    ->first();

                $buyerId = null;
                if (!$existing) {
                    // Insert new buyer record
                    $buyerId = DB::connection('sqlsrv')->table('buyer_list')->insertGetId([
                        'application_id' => $applicationId,
                        'buyer_title' => $record['buyerTitle'] ?? '',
                        'buyer_name' => $record['buyerName'],
                        'unit_no' => $record['sectionNo'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    $buyerId = $existing->id;
                }

                // Handle measurement data if provided
                if (isset($record['measurement']) && !empty($record['measurement'])) {
                    // Check if measurement record already exists
                    $existingMeasurement = DB::connection('sqlsrv')
                        ->table('st_unit_measurements')
                        ->where('application_id', $applicationId)
                        ->where('unit_no', $record['sectionNo'])
                        ->first();

                    if ($existingMeasurement) {
                        // Update existing measurement
                        DB::connection('sqlsrv')
                            ->table('st_unit_measurements')
                            ->where('application_id', $applicationId)
                            ->where('unit_no', $record['sectionNo'])
                            ->update([
                                'buyer_id' => $buyerId,
                                'measurement' => $record['measurement'],
                                'updated_at' => now()
                            ]);
                    } else {
                        // Insert new measurement record
                        DB::connection('sqlsrv')->table('st_unit_measurements')->insert([
                            'application_id' => $applicationId,
                            'buyer_id' => $buyerId,
                            'unit_no' => $record['sectionNo'],
                            'measurement' => $record['measurement'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            // Get updated records list for response
            $updatedRecords = DB::connection('sqlsrv')
                ->table('buyer_list as bl')
                ->leftJoin('st_unit_measurements as sum', function($join) use ($applicationId) {
                    $join->on('bl.unit_no', '=', 'sum.unit_no')
                         ->where('sum.application_id', '=', $applicationId);
                })
                ->where('bl.application_id', $applicationId)
                ->select('bl.id', 'bl.buyer_title', 'bl.buyer_name', 'bl.unit_no', 'bl.unit_measurement_id', 'sum.measurement')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Conveyance data updated successfully',
                'records' => $updatedRecords
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating buyers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a single buyer to the conveyance data
     */
    public function addBuyer(Request $request)
    {
        try {
            $validated = $request->validate([
                'application_id' => 'required|integer',
                'records' => 'required|array|min:1',
                'records.*.buyerName' => 'required|string',
                'records.*.sectionNo' => 'required|string',
                'records.*.buyerTitle' => 'nullable|string',
                'records.*.measurement' => 'nullable|numeric',
            ]);

            $applicationId = $validated['application_id'];
            $insertedCount = 0;

            // Process each buyer
            foreach ($validated['records'] as $record) {
                // Check if this buyer already exists
                $exists = DB::connection('sqlsrv')
                    ->table('buyer_list')
                    ->where('application_id', $applicationId)
                    ->where('buyer_name', $record['buyerName'])
                    ->where('unit_no', $record['sectionNo'])
                    ->exists();

                if (!$exists) {
                    // Insert the new buyer
                    $buyerId = DB::connection('sqlsrv')->table('buyer_list')->insertGetId([
                        'application_id' => $applicationId,
                        'buyer_title' => $record['buyerTitle'] ?? '',
                        'buyer_name' => $record['buyerName'],
                        'unit_no' => $record['sectionNo'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // Handle measurement data if provided
                    if (isset($record['measurement']) && !empty($record['measurement'])) {
                        DB::connection('sqlsrv')->table('st_unit_measurements')->insert([
                            'application_id' => $applicationId,
                            'buyer_id' => $buyerId,
                            'unit_no' => $record['sectionNo'],
                            'measurement' => $record['measurement'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                    
                    $insertedCount++;
                }
            }

            // Get all records for the response
            $allRecords = DB::connection('sqlsrv')
                ->table('buyer_list as bl')
                ->leftJoin('st_unit_measurements as sum', function($join) use ($applicationId) {
                    $join->on('bl.unit_no', '=', 'sum.unit_no')
                         ->where('sum.application_id', '=', $applicationId);
                })
                ->where('bl.application_id', $applicationId)
                ->select('bl.id', 'bl.buyer_title', 'bl.buyer_name', 'bl.unit_no', 'bl.unit_measurement_id', 'sum.measurement')
                ->get();

            return response()->json([
                'success' => true,
                'message' => "Buyers added successfully ($insertedCount new, " . 
                             (count($validated['records']) - $insertedCount) . " duplicates skipped)",
                'records' => $allRecords
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding buyers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a buyer from the conveyance data
     */
    public function deleteBuyer(Request $request)
    {
        try {
            $validated = $request->validate([
                'application_id' => 'required|integer',
                'buyer_id'       => 'required|integer',
            ]);

            // Check if the application exists and get its status
            $application = DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $validated['application_id'])
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found.'
                ], 404);
            }

            // Check if both application status and planning recommendation are approved
            if ($application->application_status == 'Approved' && $application->planning_recommendation_status == 'Approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete buyer - Both Application Status and Planning Recommendation have been approved. No further modifications are allowed.'
                ], 403);
            }

            // Delete the buyer record
            $deleted = DB::connection('sqlsrv')
                ->table('buyer_list')
                ->where('id', $validated['buyer_id'])
                ->where('application_id', $validated['application_id'])
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Buyer not found'
                ], 404);
            }

            // Also delete the measurement record if it exists
            DB::connection('sqlsrv')
                ->table('st_unit_measurements')
                ->where('buyer_id', $validated['buyer_id'])
                ->where('application_id', $validated['application_id'])
                ->delete();

            // Get remaining records
            $records = DB::connection('sqlsrv')
                ->table('buyer_list as bl')
                ->leftJoin('st_unit_measurements as sum', function($join) use ($validated) {
                    $join->on('bl.unit_no', '=', 'sum.unit_no')
                         ->where('sum.application_id', '=', $validated['application_id']);
                })
                ->where('bl.application_id', $validated['application_id'])
                ->select('bl.id', 'bl.buyer_title', 'bl.buyer_name', 'bl.unit_no', 'bl.unit_measurement_id', 'sum.measurement')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Buyer deleted successfully',
                'records' => $records
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting buyer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalize the conveyance agreement for an application
     */
    public function finalizeConveyance(Request $request)
    {
        try {
            $validated = $request->validate([
                'application_id' => 'required|integer',
                'status' => 'required|string|in:completed,pending',
            ]);

            // Check if the application has buyers
            $buyersCount = DB::connection('sqlsrv')
                ->table('buyer_list')
                ->where('application_id', $validated['application_id'])
                ->count();

            if ($buyersCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please add at least one buyer before finalizing the conveyance agreement'
                ]);
            }

            // Update the application status
            $updated = DB::connection('sqlsrv')
                ->table('mother_applications')
                ->where('id', $validated['application_id'])
                ->update([
                    'conveyance_status' => $validated['status'],
                    'conveyance_date' => now(),
                    'updated_at' => now()
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Final Conveyance Agreement submitted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update record or record not found'
            ]);
        } catch (\Exception $e) {
            Log::error('Error finalizing conveyance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the final conveyance view for an application
     */
    public function finalConveyance($id)
    {
        $application = DB::connection('sqlsrv')->table('mother_applications')
            ->where('id', $id)
            ->first();

        if (!$application) {
            return redirect()->back()->with('error', 'Application not found');
        }

        // Generate basic agreement content
        $agreementContent = "<h2>Final Conveyance Agreement</h2>";
        $agreementContent .= "<p>Application File No: <strong>" . ($application->file_no ?? 'N/A') . "</strong></p>";
        $agreementContent .= "<p>Applicant: <strong>" . ($application->owner_fullname ?? 'N/A') . "</strong></p>";
        $agreementContent .= "<p>Property Location: <strong>" . ($application->layout_name ?? 'N/A') . "</strong></p>";
        $agreementContent .= "<p>Land Use: <strong>" . ($application->land_use ?? 'N/A') . "</strong></p>";
        $agreementContent .= "<p>This document serves as the final conveyance agreement for the above mentioned property.</p>";

        return view('actions.final_conveyance', [
            'application' => $application,
            'agreementContent' => $agreementContent,
            'PageTitle' => 'Final Conveyance Agreement',
            'PageDescription' => 'Manage buyers and generate final conveyance agreement'
        ]);
    }

    public function finalConveyanceAgreement($id, $buyer_id = null)
    {
        $application = DB::connection('sqlsrv')->table('mother_applications')
            ->where('id', $id)
            ->first();

        if (!$application) {
            return redirect()->back()->with('error', 'Application not found');
        }

        // Always show the buyers management page first (with add/edit functionality)
        return view('actions.final_conveyance', [
            'application' => $application,
            'PageTitle' => 'Generate Final Conveyance',
            'PageDescription' => 'Manage buyers and generate final conveyance agreement'
        ]);
    }

    /**
     * Generate the final conveyance document with all buyers
     */
    public function generateFinalConveyanceDocument(Request $request)
    {
        try {
            $validated = $request->validate([
                'application_id' => 'required|integer'
            ]);

            $applicationId = $validated['application_id'];

            $application = DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $applicationId)
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            // Check if final conveyance is already generated
            if ($application->final_conveyance_generated == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Final Conveyance Agreement has already been generated for this application'
                ], 400);
            }

            // Get all buyers for this application
            $buyers = DB::connection('sqlsrv')->table('buyer_list')
                ->where('application_id', $applicationId)
                ->get();

            if ($buyers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No buyers found. Please add buyers first.'
                ], 400);
            }

            // Generate agreement content
            $agreementContent = $this->generateAgreementContent($application, $buyers);

            // Insert into final_conveyance table
            $finalConveyanceId = DB::connection('sqlsrv')->table('final_conveyance')->insertGetId([
                'application_id' => $applicationId,
                'fileno' => $application->fileno,
                'agreement_content' => $agreementContent,
                'generated_date' => now(),
                'status' => 'generated',
                'created_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Mark the final conveyance as generated in mother_applications
            DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $applicationId)
                ->update([
                    'final_conveyance_generated' => 1,
                    'final_conveyance_generated_at' => now(),
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Final Conveyance Agreement generated successfully',
                'final_conveyance_id' => $finalConveyanceId
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating final conveyance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating final conveyance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate the agreement content
     */
    private function generateAgreementContent($application, $buyers)
    {
        $content = "<h1>FINAL CONVEYANCE AGREEMENT</h1>";
        $content .= "<p>(For Sectional Titling and Decommissioning of Original Certificate of Occupancy)</p>";
        $content .= "<p>This Final Conveyance Agreement is made this " . date('jS \d\a\y \of F, Y') . ", between:</p>";
        
        // Original Owner
        $ownerName = '';
        if ($application->corporate_name) {
            $ownerName = $application->corporate_name;
        } elseif ($application->multiple_owners_names) {
            $ownerName = is_array(json_decode($application->multiple_owners_names, true))
                ? implode(', ', json_decode($application->multiple_owners_names, true))
                : $application->multiple_owners_names;
        } else {
            $ownerName = trim($application->first_name . ' ' . $application->middle_name . ' ' . $application->surname);
        }
        
        $content .= "<ul>";
        $content .= "<li>- The Original Owner: " . $ownerName . "</li>";
        $content .= "<li>- Property Location: " . trim($application->property_house_no . ' ' . $application->property_plot_no . ' ' . $application->property_street_name . ', ' . $application->property_district) . "</li>";
        $content .= "<li>- Decommissioned Certificate of Occupancy (CofO) Number: " . ($application->fileno ?? '[No CofO Number Available]') . "</li>";
        $content .= "<li>- Total Land Area: " . ($application->plot_size ? $application->plot_size . ' Square Meters' : '[Not Specified]') . "</li>";
        $content .= "</ul>";

        // Buyers List
        $content .= "<h2>BUYERS LIST</h2>";
        $content .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $content .= "<thead>";
        $content .= "<tr><th>SN</th><th>BUYER NAME</th><th>UNIT NO.</th><th>MEASUREMENT (SQM)</th></tr>";
        $content .= "</thead>";
        $content .= "<tbody>";
        
        foreach ($buyers as $index => $buyer) {
            $content .= "<tr>";
            $content .= "<td>" . ($index + 1) . "</td>";
            $content .= "<td>" . ($buyer->buyer_title ? $buyer->buyer_title . ' ' : '') . $buyer->buyer_name . "</td>";
            $content .= "<td>" . $buyer->unit_no . "</td>";
            $content .= "<td>" . ($buyer->measurement ?? 'N/A') . "</td>";
            $content .= "</tr>";
        }
        
        $content .= "</tbody>";
        $content .= "</table>";

        return $content;
    }

    /**
     * Get final conveyance data for an application
     */
    public function getFinalConveyance($applicationId)
    {
        try {
            $finalConveyance = DB::connection('sqlsrv')
                ->table('final_conveyance')
                ->where('application_id', $applicationId)
                ->first();

            if (!$finalConveyance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Final conveyance not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $finalConveyance
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving final conveyance: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving final conveyance: ' . $e->getMessage()
            ], 500);
        }
    }


    public function BuyersList($id)
    {
        $application = DB::connection('sqlsrv')->table('mother_applications')
            ->where('id', $id)
            ->first();

        if (!$application) {
            return redirect()->back()->with('error', 'Application not found');
        }

        return view('actions.buyers_list', [
            'application' => $application
        ]);
    }

    /**
     * Render the buyers list template with provided data
     */
    public function renderBuyersList(Request $request)
    {
        $data = $request->validate([
            'PrimaryApplication' => 'required|array',
            'conveyanceData' => 'present|array'
        ]);

        // Create a proper object from the array
        $primaryApplication = (object)$data['PrimaryApplication'];

        // Convert the conveyance data to the format expected by the template
        if (isset($data['conveyanceData']) && !empty($data['conveyanceData'])) {
            $primaryApplication->conveyance = json_encode(['records' => $data['conveyanceData']]);
        }

        return view('sectionaltitling.action_modals.buyers_list', [
            'PrimaryApplication' => $primaryApplication
        ])->render();
    }

    /**
     * Get survey data for an application
     */
    public function getSurvey($applicationId)
    {
        try {
            $survey = DB::connection('sqlsrv')
                ->table('surveyRecord')
                ->where(function ($query) use ($applicationId) {
                    $query->where('application_id', $applicationId)
                        ->orWhere('sub_application_id', $applicationId);
                })
                ->first();

            if (!$survey) {
                return response()->json([
                    'success' => false,
                    'message' => 'No survey record found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'survey' => $survey
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing survey record
     */
    public function updateSurvey(Request $request)
    {
        try {
            // Validate the request data - same validation as in store method

            // Check if the URL contains 'sub-actions' and adjust the application_id accordingly
            $applicationIdField = $request->is('*/sub-actions/*') ? 'sub_application_id' : 'application_id';

            // Validate the request data
            $validatedData = $request->validate([
                $applicationIdField => 'nullable',
                'fileno' => 'required|string|max:255',
                // Survey personnel information
                'survey_by' => 'required|string|max:255',
                'survey_by_date' => 'required|date',
                'drawn_by' => 'required|string|max:255',
                'drawn_by_date' => 'required|date',
                'checked_by' => 'required|string|max:255',
                'checked_by_date' => 'required|date',
                'approved_by' => 'required|string|max:255',
                'approved_by_date' => 'required|date',
                // Property Identification
                'plot_no' => 'nullable|string|max:255',
                'block_no' => 'nullable|string|max:255',
                'approved_plan_no' => 'nullable|string|max:255',
                'tp_plan_no' => 'nullable|string|max:255',
                // Beacon Control Information
                'beacon_control_name' => 'nullable|string|max:255',
                'Control_Beacon_Coordinate_X' => 'nullable|string|max:255',
                'Control_Beacon_Coordinate_Y' => 'nullable|string|max:255',
                // Sheet Information
                'Metric_Sheet_Index' => 'nullable|string|max:255',
                'Metric_Sheet_No' => 'nullable|string|max:255',
                'Imperial_Sheet' => 'nullable|string|max:255',
                'Imperial_Sheet_No' => 'nullable|string|max:255',
                // Location Information
                'layout_name' => 'nullable|string|max:255',
                'district_name' => 'nullable|string|max:255',
                'lga_name' => 'nullable|string|max:255',
            ]);

            // Update the record in the database
            $updated = DB::connection('sqlsrv')
                ->table('surveyRecord')
                ->where(function ($query) use ($validatedData) {
                    $query->where('application_id', $validatedData['application_id'] ?? null)
                        ->orWhere('sub_application_id', $validatedData['sub_application_id'] ?? null);
                })
                ->update($validatedData);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Survey record not found or no changes made'
                ], 404);
            }

            // Return JSON response for AJAX
            return response()->json([
                'success' => true,
                'message' => 'Survey updated successfully!'
            ]);
        } catch (\Exception $e) {
            // Log the error message
            \Log::error('Survey update error: ' . $e->getMessage());

            // Return JSON error response
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update a single buyer
     */
    public function updateSingleBuyer(Request $request)
    {
        try {
            $validated = $request->validate([
                'application_id' => 'required|integer',
                'buyer_id'       => 'required|integer',
                'buyer_title'    => 'nullable|string',
                'buyer_name'     => 'required|string',
                'unit_no'        => 'required|string',
                'measurement'    => 'nullable|numeric',
            ]);

            // Check if the application exists and get its status
            $application = DB::connection('sqlsrv')->table('mother_applications')
                ->where('id', $validated['application_id'])
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found.'
                ], 404);
            }

            // Check if both application status and planning recommendation are approved
            if ($application->application_status == 'Approved' && $application->planning_recommendation_status == 'Approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot update buyer - Both Application Status and Planning Recommendation have been approved. No further modifications are allowed.'
                ], 403);
            }

            // Update the buyer record
            $updated = DB::connection('sqlsrv')
                ->table('buyer_list')
                ->where('id', $validated['buyer_id'])
                ->where('application_id', $validated['application_id'])
                ->update([
                    'buyer_title' => $validated['buyer_title'],
                    'buyer_name'  => $validated['buyer_name'],
                    'unit_no'     => $validated['unit_no'],
                    'updated_at'  => now()
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Buyer not found or no changes made'
                ]);
            }

            // Handle measurement data if provided
            if (isset($validated['measurement']) && !empty($validated['measurement'])) {
                // Check if measurement record already exists
                $existingMeasurement = DB::connection('sqlsrv')
                    ->table('st_unit_measurements')
                    ->where('application_id', $validated['application_id'])
                    ->where('unit_no', $validated['unit_no'])
                    ->first();

                if ($existingMeasurement) {
                    // Update existing measurement
                    DB::connection('sqlsrv')
                        ->table('st_unit_measurements')
                        ->where('application_id', $validated['application_id'])
                        ->where('unit_no', $validated['unit_no'])
                        ->update([
                            'buyer_id' => $validated['buyer_id'],
                            'measurement' => $validated['measurement'],
                            'updated_at' => now()
                        ]);
                } else {
                    // Insert new measurement record
                    DB::connection('sqlsrv')->table('st_unit_measurements')->insert([
                        'application_id' => $validated['application_id'],
                        'buyer_id' => $validated['buyer_id'],
                        'unit_no' => $validated['unit_no'],
                        'measurement' => $validated['measurement'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Buyer information updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating buyer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}