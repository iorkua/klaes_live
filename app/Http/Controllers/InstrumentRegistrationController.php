<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InstrumentRegistrationController extends Controller
{
    private function getApplication($id)
    {
        $application = DB::connection('sqlsrv')->table('mother_applications')
            ->where('id', $id)
            ->first();

        if (!$application) {
            return response()->json(['error' => 'Application not found'], 404);
        }

        return $application;
    }
    
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

    public function InstrumentRegistration()
    {
        $PageTitle = 'Instrument Registration ';
        $PageDescription = '';

        try {
            // Initialize default completion status for subapplications that don't have it set
            $this->initializeDefaultCompletionStatus();
            
            // Automatically insert ST Fragmentation details for approved mother applications
            $this->autoRegisterSTFragmentation();
            
            // Get approved subapplications and create both ST Assignment and Sectional Titling records for each
            $approvedSubapplications = DB::connection('sqlsrv')->table('subapplications as s')
                ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
                ->leftJoin('users', 's.created_by', '=', 'users.id')
                ->where('s.planning_recommendation_status', 'Approved')
                ->where('s.application_status', 'Approved')
                ->select(
                    's.id',
                    's.fileno',
                    's.deeds_completion_status',
                    DB::raw("CONCAT(COALESCE(s.applicant_title,''), ' ', COALESCE(s.first_name,''), ' ', COALESCE(s.middle_name,''), ' ', COALESCE(s.surname,''), COALESCE(s.corporate_name,''), COALESCE(s.rc_number,''), COALESCE(s.multiple_owners_names,'')) as sub_applicant"),
                    DB::raw("CONCAT(COALESCE(m.applicant_title,''), ' ', COALESCE(m.first_name,''), ' ', COALESCE(m.middle_name,''), ' ', COALESCE(m.surname,''), COALESCE(m.corporate_name,''), COALESCE(m.rc_number,''), COALESCE(m.multiple_owners_names,'')) as mother_applicant"),
                    'm.property_lga as lga',
                    'm.property_district as district',
                    'm.plot_size as size',
                    'm.property_plot_no as plotNumber',
                    'm.np_fileno', // Add np_fileno for parent_fileNo
                    // Add property description fields from mother application
                    'm.property_house_no',
                    'm.property_street_name',
                    's.created_by as reg_created_by',
                    's.created_at',
                    DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as reg_creator_name")
                )
                ->get();

            // Get approved mother applications for ST Fragmentation records
            $approvedMotherApplications = DB::connection('sqlsrv')->table('mother_applications as m')
                ->leftJoin('users', 'm.created_by', '=', 'users.id')
                ->where('m.planning_recommendation_status', 'Approved')
                ->where('m.application_status', 'Approved')
                ->select(
                    'm.id',
                    'm.fileno',
                    'm.np_fileno',
                    'm.applicant_title',
                    'm.first_name',
                    'm.middle_name',
                    'm.surname',
                    'm.corporate_name',
                    'm.rc_number',
                    'm.multiple_owners_names',
                    'm.owner_fullname as mother_applicant',
                    'm.property_lga as lga',
                    'm.property_district as district',
                    'm.plot_size as size',
                    'm.property_plot_no as plotNumber',
                    'm.property_house_no',
                    'm.property_street_name',
                    'm.created_by as reg_created_by',
                    'm.created_at',
                    DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as reg_creator_name")
                )
                ->get();

            // Get unregistered instruments from instrument_registration table
            $unregisteredInstruments = DB::connection('sqlsrv')->table('instrument_registration')
                ->leftJoin('users', 'instrument_registration.created_by', '=', 'users.id')
                ->where(function ($q) {
                    $q->where('instrument_registration.status', '!=', 'registered')
                      ->orWhereNull('instrument_registration.status');
                })
                ->select(
                    'instrument_registration.*',
                    DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as reg_creator_name")
                )
                ->get();

            // Create collection for all instruments
            $allInstruments = collect();

            // Add unregistered instruments from instrument_registration table
            foreach ($unregisteredInstruments as $instrument) {
                // Determine the file number to use
                $fileNo = $instrument->MLSFileNo ?? $instrument->KAGISFileNO ?? $instrument->NewKANGISFileNo ?? 'N/A';
                
                $instrumentRecord = (object)[
                    'id' => 'instr_reg_' . $instrument->id,
                    'fileno' => $fileNo,
                    'parent_fileNo' => null, // Other instruments don't have parent file numbers
                    'Deeds_Serial_No' => $instrument->particularsRegistrationNumber ?? null,
                    'instrument_type' => $instrument->instrument_type ?? 'Other Instrument',
                    'Grantor' => $instrument->Grantor ?? 'N/A',
                    'Grantee' => $instrument->Grantee ?? 'N/A',
                    'GrantorAddress' => $instrument->GrantorAddress ?? '',
                    'GranteeAddress' => $instrument->GranteeAddress ?? '',
                    'duration' => $instrument->duration ?? $instrument->leasePeriod ?? '',
                    'leasePeriod' => $instrument->leasePeriod ?? '',
                    'propertyDescription' => $instrument->propertyDescription ?? '',
                    'lga' => $instrument->lga ?? '',
                    'district' => $instrument->district ?? '',
                    'size' => $instrument->size ?? '',
                    'plotNumber' => $instrument->plotNumber ?? '',
                    'deeds_date' => $instrument->instrumentDate ?? null,
                    'solicitorName' => $instrument->solicitorName ?? '',
                    'solicitorAddress' => $instrument->solicitorAddress ?? '',
                    'status' => $instrument->status ?? 'pending',
                    'land_use' => $instrument->landUseType ?? '',
                    'reg_created_by' => $instrument->created_by,
                    'created_at' => $instrument->created_at,
                    'reg_creator_name' => $instrument->reg_creator_name ?? '',
                    'instrument_category' => 'Other Instruments',
                    'STM_Ref' => null, // Other instruments don't have STM_Ref until registered
                    'original_instrument_id' => $instrument->id,
                    // Add captured date and reg date fields
                    'captured_date' => $instrument->instrumentDate ?? $instrument->created_at, // from instrument_registration.instrumentDate or created_at
                    'reg_date' => null // Other instruments don't have reg_date until registered
                ];

                $allInstruments->push($instrumentRecord);
            }

            // For each approved subapplication, create both ST Assignment and Sectional Titling records
            foreach ($approvedSubapplications as $subApp) {
                // Get registration details from registered_instruments table
                $stRegistration = DB::connection('sqlsrv')->table('registered_instruments')
                    ->where('StFileNo', $subApp->fileno)
                    ->where('instrument_type', 'ST Assignment (Transfer of Title)')
                    ->where('status', 'registered')
                    ->first();
                
                $sectionalRegistration = DB::connection('sqlsrv')->table('registered_instruments')
                    ->where('StFileNo', $subApp->fileno)
                    ->where('instrument_type', 'Sectional Titling CofO')
                    ->where('status', 'registered')
                    ->first();

                // Build property description from mother application details
                $propertyDescription = '';
                $propertyParts = [];
                
                if (!empty($subApp->property_house_no)) {
                    $propertyParts[] = 'House No: ' . $subApp->property_house_no;
                }
                if (!empty($subApp->plotNumber)) {
                    $propertyParts[] = 'Plot No: ' . $subApp->plotNumber;
                }
                if (!empty($subApp->property_street_name)) {
                    $propertyParts[] = $subApp->property_street_name;
                }
                if (!empty($subApp->district)) {
                    $propertyParts[] = $subApp->district;
                }
                if (!empty($subApp->lga)) {
                    $propertyParts[] = $subApp->lga;
                }
                
                $propertyDescription = implode(', ', $propertyParts);
                if (empty($propertyDescription)) {
                    $propertyDescription = 'Property details not available';
                }

                // Create ST Assignment (Transfer of Title) record
                $stAssignmentRecord = (object)[
                    'id' => $subApp->id . '_st_assignment',
                    'fileno' => $subApp->fileno, // fileno from subapplications table
                    'parent_fileNo' => $subApp->np_fileno, // np_fileno from mother_applications table
                    'Deeds_Serial_No' => $stRegistration->particularsRegistrationNumber ?? null,
                    'instrument_type' => 'ST Assignment (Transfer of Title)',
                    'Grantor' => $subApp->mother_applicant, // Grantor should be from mother application applicant details
                    'Grantee' => $subApp->sub_applicant,
                    'GrantorAddress' => '',
                    'GranteeAddress' => '',
                    'duration' => '',
                    'leasePeriod' => '',
                    'propertyDescription' => $propertyDescription,
                    'lga' => $subApp->lga,
                    'district' => $subApp->district,
                    'size' => $subApp->size,
                    'plotNumber' => $subApp->plotNumber,
                    'deeds_date' => $stRegistration->instrumentDate ?? null,
                    'solicitorName' => '',
                    'solicitorAddress' => '',
                    'status' => $stRegistration ? 'registered' : 'pending',
                    'land_use' => '',
                    'reg_created_by' => $subApp->reg_created_by,
                    'created_at' => $subApp->created_at,
                    'reg_creator_name' => $subApp->reg_creator_name,
                    'instrument_category' => 'ST Assignment',
                    'STM_Ref' => $stRegistration->STM_Ref ?? null,
                    'original_subapp_id' => $subApp->id,
                    // Add captured date and reg date fields
                    'captured_date' => $subApp->created_at, // from subapplications.created_at
                    'reg_date' => $stRegistration->instrumentDate ?? null // from registered_instruments.instrumentDate
                ];

                // Create Sectional Titling CofO record
                $sectionalRecord = (object)[
                    'id' => $subApp->id . '_sectional_cofo',
                    'fileno' => $subApp->fileno, // fileno from subapplications table
                    'parent_fileNo' => $subApp->np_fileno, // np_fileno from mother_applications table
                    'Deeds_Serial_No' => $sectionalRegistration->particularsRegistrationNumber ?? null,
                    'instrument_type' => 'Sectional Titling CofO',
                    'Grantor' => 'Kano State Government', // Always Kano State Government for Sectional Titling CofO
                    'Grantee' => $subApp->sub_applicant,
                    'GrantorAddress' => '',
                    'GranteeAddress' => '',
                    'duration' => '',
                    'leasePeriod' => '',
                    'propertyDescription' => $propertyDescription,
                    'lga' => $subApp->lga,
                    'district' => $subApp->district,
                    'size' => $subApp->size,
                    'plotNumber' => $subApp->plotNumber,
                    'deeds_date' => $sectionalRegistration->instrumentDate ?? null,
                    'solicitorName' => '',
                    'solicitorAddress' => '',
                    'status' => $sectionalRegistration ? 'registered' : 'pending',
                    'land_use' => '',
                    'reg_created_by' => $subApp->reg_created_by,
                    'created_at' => $subApp->created_at,
                    'reg_creator_name' => $subApp->reg_creator_name,
                    'instrument_category' => 'Sectional Titling',
                    'STM_Ref' => $sectionalRegistration->STM_Ref ?? null,
                    'original_subapp_id' => $subApp->id,
                    // Add captured date and reg date fields
                    'captured_date' => $subApp->created_at, // from subapplications.created_at
                    'reg_date' => $sectionalRegistration->instrumentDate ?? null // from registered_instruments.instrumentDate
                ];

                $allInstruments->push($stAssignmentRecord);
                $allInstruments->push($sectionalRecord);
            }

            // Add ST Fragmentation records from approved mother applications
            foreach ($approvedMotherApplications as $motherApp) {
                // Check if ST Fragmentation is already registered
                $stFragmentationRegistration = DB::connection('sqlsrv')->table('registered_instruments')
                    ->where('MLSFileNo', $motherApp->fileno)
                    ->where('instrument_type', 'ST Fragmentation')
                    ->where('status', 'registered')
                    ->first();

                // Build property description for ST Fragmentation
                $propertyDescription = '';
                $propertyParts = [];
                
                if (!empty($motherApp->property_house_no)) {
                    $propertyParts[] = 'House No: ' . $motherApp->property_house_no;
                }
                if (!empty($motherApp->plotNumber)) {
                    $propertyParts[] = 'Plot No: ' . $motherApp->plotNumber;
                }
                if (!empty($motherApp->property_street_name)) {
                    $propertyParts[] = $motherApp->property_street_name;
                }
                if (!empty($motherApp->district)) {
                    $propertyParts[] = $motherApp->district;
                }
                if (!empty($motherApp->lga)) {
                    $propertyParts[] = $motherApp->lga;
                }
                
                $propertyDescription = implode(', ', $propertyParts);
                if (empty($propertyDescription)) {
                    $propertyDescription = 'Property details not available';
                }

                // Build mother applicant name properly for ST Fragmentation Grantee
                $motherApplicantName = '';
                $motherApplicantParts = [];
                
                if (!empty($motherApp->applicant_title)) {
                    $motherApplicantParts[] = $motherApp->applicant_title;
                }
                if (!empty($motherApp->first_name)) {
                    $motherApplicantParts[] = $motherApp->first_name;
                }
                if (!empty($motherApp->middle_name)) {
                    $motherApplicantParts[] = $motherApp->middle_name;
                }
                if (!empty($motherApp->surname)) {
                    $motherApplicantParts[] = $motherApp->surname;
                }
                if (!empty($motherApp->corporate_name)) {
                    $motherApplicantParts[] = $motherApp->corporate_name;
                }
                if (!empty($motherApp->rc_number)) {
                    $motherApplicantParts[] = $motherApp->rc_number;
                }
                if (!empty($motherApp->multiple_owners_names)) {
                    $motherApplicantParts[] = $motherApp->multiple_owners_names;
                }
                
                $motherApplicantName = implode(' ', $motherApplicantParts);
                if (empty($motherApplicantName)) {
                    $motherApplicantName = $motherApp->mother_applicant ?? 'N/A';
                }

                // Create ST Fragmentation record
                $stFragmentationRecord = (object)[
                    'id' => $motherApp->id . '_st_fragmentation',
                    'fileno' => $motherApp->np_fileno ?? $motherApp->fileno, // np_fileno from mother_applications table as fileNo
                    'parent_fileNo' => $motherApp->fileno, // fileno from mother_applications table as parent_fileNo
                    'Deeds_Serial_No' => $stFragmentationRegistration->particularsRegistrationNumber ?? null,
                    'instrument_type' => 'ST Fragmentation',
                    'Grantor' => 'Kano State Government', // As specified in requirements
                    'Grantee' => $motherApplicantName, // Use properly built mother applicant name
                    'GrantorAddress' => '',
                    'GranteeAddress' => '',
                    'duration' => '',
                    'leasePeriod' => '',
                    'propertyDescription' => $propertyDescription,
                    'lga' => $motherApp->lga,
                    'district' => $motherApp->district,
                    'size' => $motherApp->size,
                    'plotNumber' => $motherApp->plotNumber,
                    'deeds_date' => $stFragmentationRegistration->instrumentDate ?? null,
                    'solicitorName' => '',
                    'solicitorAddress' => '',
                    'status' => $stFragmentationRegistration ? 'registered' : 'pending',
                    'land_use' => '',
                    'reg_created_by' => $motherApp->reg_created_by,
                    'created_at' => $motherApp->created_at,
                    'reg_creator_name' => $motherApp->reg_creator_name,
                    'instrument_category' => 'ST Fragmentation',
                    'STM_Ref' => $stFragmentationRegistration->STM_Ref ?? null,
                    'original_mother_app_id' => $motherApp->id,
                    // Add captured date and reg date fields
                    'captured_date' => $motherApp->created_at, // from mother_applications.created_at
                    'reg_date' => $stFragmentationRegistration->instrumentDate ?? null // from registered_instruments.instrumentDate
                ];

                $allInstruments->push($stFragmentationRecord);
            }

            Log::info('Instrument Registration data loaded', [
                'approved_subapplications' => $approvedSubapplications->count(),
                'approved_mother_applications' => $approvedMotherApplications->count(),
                'total_instruments' => $allInstruments->count(),
                'st_assignment_count' => $allInstruments->where('instrument_type', 'ST Assignment (Transfer of Title)')->count(),
                'sectional_titling_count' => $allInstruments->where('instrument_type', 'Sectional Titling CofO')->count(),
                'st_fragmentation_count' => $allInstruments->where('instrument_type', 'ST Fragmentation')->count()
            ]);

            // Count statuses
            $pendingCount = $allInstruments->where('status', 'pending')->count();
            $registeredCount = $allInstruments->where('status', 'registered')->count();
            $rejectedCount = 0; // No rejected status in this context
            $totalCount = $allInstruments->count();

            // Process property descriptions and durations
            foreach ($allInstruments as $application) {
                if (empty($application->propertyDescription)) {
                    $application->property_description = 
                        (!empty($application->district) ? $application->district . ', ' : '') .
                        (!empty($application->lga) ? $application->lga . ', ' : '') .
                        (!empty($application->state) ? $application->state : '');
                } else {
                    $application->property_description = $application->propertyDescription;
                }
                
                $application->duration = $application->duration ?? $application->leasePeriod ?? 'N/A';
            }

            Log::info('Final instrument counts', [
                'total_count' => $totalCount,
                'pending_count' => $pendingCount,
                'registered_count' => $registeredCount,
                'rejected_count' => $rejectedCount,
            ]);

            $approvedApplications = $allInstruments;
            
            return view('instrument_registration.index', compact(
                'approvedApplications',
                'PageTitle',
                'PageDescription',
                'pendingCount',
                'registeredCount',
                'rejectedCount',
                'totalCount'
            ));
            
        } catch (\Exception $e) {
            Log::error('Error in InstrumentRegistration method', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $approvedApplications = collect();
            $pendingCount = $registeredCount = $rejectedCount = $totalCount = 0;
            
            return view('instrument_registration.index', compact(
                'approvedApplications',
                'PageTitle',
                'PageDescription',
                'pendingCount',
                'registeredCount',
                'rejectedCount',
                'totalCount'
            ))->with('error', 'Error loading instrument data: ' . $e->getMessage());
        }
    }

    public function view($id)
    {
        $PageTitle = 'View Instrument Registration';
        $PageDescription = '';
        
        try {
            $application = null;
            
            // Handle composite IDs for ST Assignment and Sectional Titling
            if (strpos($id, '_st_assignment') !== false || strpos($id, '_sectional_cofo') !== false) {
                $originalId = str_replace(['_st_assignment', '_sectional_cofo'], '', $id);
                $instrumentType = strpos($id, '_st_assignment') !== false ? 'ST Assignment (Transfer of Title)' : 'Sectional Titling CofO';
                
                // Get the subapplication details
                $subApplication = DB::connection('sqlsrv')->table('subapplications as s')
                    ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
                    ->leftJoin('users', 's.created_by', '=', 'users.id')
                    ->where('s.id', $originalId)
                    ->select(
                        's.*',
                        DB::raw("CONCAT(COALESCE(s.applicant_title,''), ' ', COALESCE(s.first_name,''), ' ', COALESCE(s.surname,''), COALESCE(s.corporate_name,''), COALESCE(s.multiple_owners_names,'')) as sub_applicant"),
                        'm.property_lga as lga',
                        'm.property_district as district',
                        'm.plot_size as size',
                        'm.property_plot_no as plotNumber',
                        'm.np_fileno', // Include np_fileno for parent_fileNo
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as reg_creator_name")
                    )
                    ->first();
                
                if (!$subApplication) {
                    Log::error('Subapplication not found', ['id' => $originalId]);
                    return redirect()->route('instrument_registration.index')->with('error', 'Instrument not found');
                }
                
                // Check if this instrument is registered and get registration details
                $registeredInstrument = DB::connection('sqlsrv')->table('registered_instruments')
                    ->leftJoin('users', 'registered_instruments.created_by', '=', 'users.id')
                    ->where('registered_instruments.StFileNo', $subApplication->fileno)
                    ->where('registered_instruments.instrument_type', $instrumentType)
                    ->where('registered_instruments.status', 'registered')
                    ->select(
                        'registered_instruments.*',
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as reg_creator_name")
                    )
                    ->first();
                
                // Create a combined application object
                $application = (object)[
                    'id' => $id,
                    'fileno' => $subApplication->fileno,
                    'parent_fileNo' => $subApplication->np_fileno, // Include parent_fileNo
                    'instrument_type' => $instrumentType,
                    'Grantor' => $subApplication->sub_applicant,
                    'Grantee' => $subApplication->sub_applicant,
                    'Applicant_Name' => $subApplication->sub_applicant,
                    'lga' => $subApplication->lga,
                    'district' => $subApplication->district,
                    'size' => $subApplication->size,
                    'plotNumber' => $subApplication->plotNumber,
                    'reg_creator_name' => $subApplication->reg_creator_name,
                    'created_at' => $subApplication->created_at,
                    'updated_at' => $subApplication->updated_at ?? $subApplication->created_at,
                    'source_type' => 'subapplication',
                    // Registration details if available
                    'particularsRegistrationNumber' => $registeredInstrument->particularsRegistrationNumber ?? null,
                    'Deeds_Serial_No' => $registeredInstrument->particularsRegistrationNumber ?? null,
                    'STM_Ref' => $registeredInstrument->STM_Ref ?? null,
                    'instrumentDate' => $registeredInstrument->instrumentDate ?? null,
                    'deeds_date' => $registeredInstrument->deeds_date ?? $registeredInstrument->instrumentDate ?? null,
                    'deeds_time' => $registeredInstrument->deeds_time ?? null,
                    'status' => $registeredInstrument ? 'registered' : 'pending',
                    'reg_status' => $registeredInstrument ? 'registered' : 'pending',
                    'propertyDescription' => $registeredInstrument->propertyDescription ?? '',
                    'GrantorAddress' => $registeredInstrument->GrantorAddress ?? '',
                    'GranteeAddress' => $registeredInstrument->GranteeAddress ?? '',
                    'duration' => $registeredInstrument->duration ?? '',
                    'solicitorName' => $registeredInstrument->solicitorName ?? '',
                    'solicitorAddress' => $registeredInstrument->solicitorAddress ?? '',
                    // Additional properties that might be referenced in the view
                    'Tenure_Period' => $registeredInstrument->Tenure_Period ?? null,
                    'serial_no' => $registeredInstrument->serial_no ?? null,
                    'page_no' => $registeredInstrument->page_no ?? null,
                    'reg_page_no' => $registeredInstrument->page_no ?? null,
                    'volume_no' => $registeredInstrument->volume_no ?? null,
                    'Occupation' => $subApplication->occupation ?? null,
                    'NoOfUnits' => null,
                    'NoOfBlocks' => null,
                    'NoOfSections' => null,
                    'property_street_name' => null,
                    'property_district' => $subApplication->district,
                    'property_lga' => $subApplication->lga,
                    'land_use' => null,
                    'commercial_type' => null,
                    'industrial_type' => null,
                    'residential_type' => null
                ];
                
            } else {
                // Regular registered instrument ID
                $application = DB::connection('sqlsrv')
                    ->table('registered_instruments')
                    ->leftJoin('users', 'registered_instruments.created_by', '=', 'users.id')
                    ->select(
                        'registered_instruments.*',
                        DB::raw("CONCAT(COALESCE(users.first_name, ''), ' ', COALESCE(users.last_name, '')) as reg_creator_name")
                    )
                    ->where('registered_instruments.id', $id)
                    ->first();
                
                if ($application) {
                    $application->source_type = 'registered_instruments';
                    $application->fileno = $application->MLSFileNo ?? $application->KAGISFileNO ?? $application->NewKANGISFileNo ?? $application->StFileNo;
                    // Ensure all required properties exist
                    $application->Deeds_Serial_No = $application->particularsRegistrationNumber ?? null;
                    $application->reg_status = $application->status ?? 'pending';
                    $application->Applicant_Name = $application->Grantor ?? $application->Grantee ?? 'N/A';
                    $application->reg_page_no = $application->page_no ?? null;
                    $application->property_district = $application->district ?? null;
                    $application->property_lga = $application->lga ?? null;
                    // Set default values for properties that might not exist
                    $application->Tenure_Period = $application->Tenure_Period ?? null;
                    $application->Occupation = $application->Occupation ?? null;
                    $application->NoOfUnits = $application->NoOfUnits ?? null;
                    $application->NoOfBlocks = $application->NoOfBlocks ?? null;
                    $application->NoOfSections = $application->NoOfSections ?? null;
                    $application->property_street_name = $application->property_street_name ?? null;
                    $application->land_use = $application->land_use ?? null;
                    $application->commercial_type = $application->commercial_type ?? null;
                    $application->industrial_type = $application->industrial_type ?? null;
                    $application->residential_type = $application->residential_type ?? null;
                }
            }

            if (!$application) {
                Log::error('Instrument not found', ['id' => $id]);
                return redirect()->route('instrument_registration.index')->with('error', 'Instrument not found');
            }

            return view('instrument_registration.view', compact('application', 'PageTitle', 'PageDescription'));
        } catch (\Exception $e) {
            Log::error('Error in view method', [
                'id' => $id, 
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('instrument_registration.index')
                ->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function registerSingle(Request $request)
    {
        try {
            // Validate ST Assignment and Sectional Titling CofO requirements
            $instrumentType = $request->instrument_type;
            if (in_array($instrumentType, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])) {
                // For these instrument types, we need to ensure both StFileNo and instrument type are properly validated
                $request->validate([
                    'instrument_type' => 'required|string',
                    'file_no' => 'required|string', // This will be used as StFileNo
                ], [
                    'instrument_type.required' => 'Instrument type is required for ST Assignment and Sectional Titling CofO',
                    'file_no.required' => 'File number (StFileNo) is required for ST Assignment and Sectional Titling CofO',
                ]);
                
                // Additional validation to ensure both types are registered for each application
                $fileNo = $request->file_no;
                $existingRegistrations = DB::connection('sqlsrv')->table('registered_instruments')
                    ->where('StFileNo', $fileNo)
                    ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                    ->pluck('instrument_type')
                    ->toArray();
                
                // Check if we're trying to register the same type twice for the same file
                if (in_array($instrumentType, $existingRegistrations)) {
                    return response()->json([
                        'success' => false, 
                        'error' => "A {$instrumentType} registration already exists for file number {$fileNo}"
                    ], 422);
                }
                
                // Log the registration attempt for tracking
                Log::info('ST/Sectional Titling registration attempt', [
                    'file_no' => $fileNo,
                    'instrument_type' => $instrumentType,
                    'existing_registrations' => $existingRegistrations
                ]);
            }
            
            $applicationId = $request->mother_application_id;
            $sourceRecord = null;
            $sourceTable = null;
            
            // Handle composite IDs for ST Assignment and Sectional Titling
            if (strpos($applicationId, '_st_assignment') !== false || strpos($applicationId, '_sectional_cofo') !== false) {
                $originalId = str_replace(['_st_assignment', '_sectional_cofo'], '', $applicationId);
                $sourceRecord = DB::connection('sqlsrv')->table('subapplications')->where('id', $originalId)->first();
                if ($sourceRecord) {
                    $sourceTable = 'subapplications';
                    // Add the original ID for proper status update
                    $sourceRecord->original_id = $originalId;
                }
            } elseif (strpos($applicationId, 'instr_reg_') === 0) {
                // Handle instrument_registration IDs that start with 'instr_reg_'
                $originalId = str_replace('instr_reg_', '', $applicationId);
                $sourceRecord = DB::connection('sqlsrv')->table('instrument_registration')->where('id', $originalId)->first();
                if ($sourceRecord) {
                    $sourceTable = 'instrument_registration';
                }
            } else {
                // Only check subapplications if the ID is numeric
                if (is_numeric($applicationId)) {
                    $sourceRecord = DB::connection('sqlsrv')->table('subapplications')->where('id', $applicationId)->first();
                    if ($sourceRecord) {
                        $sourceTable = 'subapplications';
                    }
                }
                
                if (!isset($sourceRecord)) {
                    $sourceRecord = DB::connection('sqlsrv')->table('instrument_registration')->where('id', $applicationId)->first();
                    if ($sourceRecord) {
                        $sourceTable = 'instrument_registration';
                    } else {
                        $sourceRecord = DB::connection('sqlsrv')->table('mother_applications')->where('id', $applicationId)->first();
                        if ($sourceRecord) {
                            $sourceTable = 'mother_applications';
                        }
                    }
                }
            }
                
            if (!$sourceRecord) {
                return response()->json(['success' => false, 'error' => 'Source record not found in any table'], 404);
            }
            
            $serialData = $this->getNextSerialNumber()->getData(true);
            $stmReference = $this->generateSTMReference();
            $dataToInsert = $this->prepareRegistrationData($sourceRecord, $sourceTable, $request, $serialData, $stmReference);
            
            $newId = DB::connection('sqlsrv')->table('registered_instruments')->insertGetId($dataToInsert);
            
            // Update status using original ID if it's a composite ID
            $updateId = isset($sourceRecord->original_id) ? $sourceRecord->original_id : $applicationId;
            
            // For instrument_registration IDs, we need to extract the numeric part
            if ($sourceTable === 'instrument_registration' && strpos($updateId, 'instr_reg_') === 0) {
                $updateId = str_replace('instr_reg_', '', $updateId);
            }
            
            $this->updateSourceRecordStatus($updateId, $sourceTable);
            
            // Update instrument completion status for ST Assignment and Sectional Titling
            if (in_array($instrumentType, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO']) && $sourceTable === 'subapplications') {
                $this->updateInstrumentCompletionStatus($updateId, $instrumentType, 'Registered');
            }
            
            // Check if both ST Assignment and Sectional Titling are now registered for this file
            if (in_array($instrumentType, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])) {
                $this->checkBothTypesRegistered($request->file_no ?? $sourceRecord->fileno);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Instrument registered successfully',
                'serial_data' => $serialData,
                'stm_ref' => $stmReference,
                'record_id' => $newId,
                'source_table' => $sourceTable
            ]);
        } catch (\Exception $e) {
            Log::error('Error in registerSingle', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => 'Failed to register: ' . $e->getMessage()], 500);
        }
    }

    private function prepareRegistrationData($sourceRecord, $sourceTable, $request, $serialData, $stmReference)
    {
         // Convert array inputs to comma-separated strings
         if (is_array($request->instrument_type)) {
             $request->instrument_type = implode(',', $request->instrument_type);
         }
         if (is_array($request->Grantor)) {
            $request->Grantor = implode(',', $request->Grantor);
         }
         if (is_array($request->Grantee)) {
             $request->Grantee = implode(',', $request->Grantee);
         }
        
        // Determine StFileNo based on instrument type and source
        $stFileNo = null;
        if (in_array($request->instrument_type, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])) {
            $stFileNo = $request->file_no ?? $sourceRecord->fileno ?? null;
        }
        
        // Override grantor for ST Assignment and Sectional Titling CofO
        $grantor = $request->Grantor;
        if (in_array($request->instrument_type, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])) {
            $grantor = 'Kano State Government';
        }
        
        $baseData = [
            'particularsRegistrationNumber' => $serialData['deeds_serial_no'],
            'STM_Ref' => $stmReference,
            'instrument_type' => $request->instrument_type,
            'Grantor' => $grantor, // Use the overridden grantor value
            'Grantee' => $request->Grantee,
            'instrumentDate' => $request->deeds_date,
            'deeds_date' => $request->deeds_date,
            'deeds_time' => $request->deeds_time,
            'serial_no' => $serialData['serial_no'],
            'page_no' => $serialData['page_no'],
            'volume_no' => $serialData['volume_no'],
            'status' => 'registered',
            'StFileNo' => $stFileNo, // Add StFileNo field
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now()
        ];

        switch ($sourceTable) {
            case 'instrument_registration':
                return array_merge($baseData, [
                    'MLSFileNo' => $sourceRecord->MLSFileNo ?? $request->file_no,
                    'KAGISFileNO' => $sourceRecord->KAGISFileNO ?? null,
                    'NewKANGISFileNo' => $sourceRecord->NewKANGISFileNo ?? null,
                    'rootRegistrationNumber' => $sourceRecord->rootRegistrationNumber ?? null,
                    'GrantorAddress' => $request->GrantorAddress ?? $sourceRecord->GrantorAddress ?? '',
                    'GranteeAddress' => $request->GranteeAddress ?? $sourceRecord->GranteeAddress ?? '',
                    'mortgagor' => $sourceRecord->mortgagor ?? null,
                    'mortgagorAddress' => $sourceRecord->mortgagorAddress ?? null,
                    'mortgagee' => $sourceRecord->mortgagee ?? null,
                    'mortgageeAddress' => $sourceRecord->mortgageeAddress ?? null,
                    'loanAmount' => $sourceRecord->loanAmount ?? null,
                    'interestRate' => $sourceRecord->interestRate ?? null,
                    'duration' => $request->duration ?? $sourceRecord->duration ?? null,
                    'assignor' => $sourceRecord->assignor ?? null,
                    'assignorAddress' => $sourceRecord->assignorAddress ?? null,
                    'assignee' => $sourceRecord->assignee ?? null,
                    'assigneeAddress' => $sourceRecord->assigneeAddress ?? null,
                    'lessor' => $sourceRecord->lessor ?? null,
                    'lessorAddress' => $sourceRecord->lessorAddress ?? null,
                    'lessee' => $sourceRecord->lessee ?? null,
                    'lesseeAddress' => $sourceRecord->lesseeAddress ?? null,
                    'leasePeriod' => $sourceRecord->leasePeriod ?? null,
                    'leaseTerms' => $sourceRecord->leaseTerms ?? null,
                    'propertyDescription' => $request->propertyDescription ?? $sourceRecord->propertyDescription ?? '',
                    'propertyAddress' => $sourceRecord->propertyAddress ?? null,
                    'lga' => $request->lga ?? $sourceRecord->lga ?? '',
                    'district' => $request->district ?? $sourceRecord->district ?? '',
                    'size' => $request->plotSize ?? $sourceRecord->size ?? '',
                    'plotNumber' => $request->plotNumber ?? $sourceRecord->plotNumber ?? '',
                    'landUseType' => $sourceRecord->landUseType ?? null,
                    'solicitorName' => $sourceRecord->solicitorName ?? null,
                    'solicitorAddress' => $sourceRecord->solicitorAddress ?? null,
                ]);

            case 'mother_applications':
                return array_merge($baseData, [
                    'MLSFileNo' => $sourceRecord->fileno ?? $request->file_no,
                    'lga' => $sourceRecord->property_lga ?? '',
                    'district' => $sourceRecord->property_district ?? '',
                    'size' => $sourceRecord->plot_size ?? '',
                    'plotNumber' => $sourceRecord->property_plot_no ?? '',
                ]);

            case 'subapplications':
                $motherApp = DB::connection('sqlsrv')->table('mother_applications')->where('id', $sourceRecord->main_application_id)->first();
                
                // Build property description for ST Assignment and Sectional Titling CofO
                $propertyDescription = $request->propertyDescription ?? '';
                if (empty($propertyDescription) && in_array($request->instrument_type, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])) {
                    $propertyParts = [];
                    
                    if (!empty($motherApp->property_house_no)) {
                        $propertyParts[] = 'House No: ' . $motherApp->property_house_no;
                    }
                    if (!empty($motherApp->property_plot_no)) {
                        $propertyParts[] = 'Plot No: ' . $motherApp->property_plot_no;
                    }
                    if (!empty($motherApp->property_street_name)) {
                        $propertyParts[] = $motherApp->property_street_name;
                    }
                    if (!empty($motherApp->property_district)) {
                        $propertyParts[] = $motherApp->property_district;
                    }
                    if (!empty($motherApp->property_lga)) {
                        $propertyParts[] = $motherApp->property_lga;
                    }
                    
                    $propertyDescription = implode(', ', $propertyParts);
                    if (empty($propertyDescription)) {
                        $propertyDescription = 'Property details not available';
                    }
                }
                
                // CRITICAL FIX: For ST Assignment and Sectional Titling CofO, set parent_fileNo to np_fileno from mother application
                $parentFileNo = null;
                if (in_array($request->instrument_type, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])) {
                    $parentFileNo = $motherApp->np_fileno ?? null;
                }
                
                return array_merge($baseData, [
                    'MLSFileNo' => $sourceRecord->fileno ?? $request->file_no,
                    'parent_fileNo' => $parentFileNo, // FIXED: Add parent_fileNo field for ST Assignment and Sectional Titling CofO
                    'lga' => $motherApp->property_lga ?? '',
                    'district' => $motherApp->property_district ?? '',
                    'size' => $motherApp->plot_size ?? '',
                    'plotNumber' => $motherApp->property_plot_no ?? '',
                    'propertyDescription' => $propertyDescription,
                ]);

            default:
                return $baseData;
        }
    }

    /**
     * Check if both ST Assignment and Sectional Titling CofO are registered for a file
     * and update the subapplication status accordingly
     */
    private function checkBothTypesRegistered($fileNo)
    {
        try {
            $registrations = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('StFileNo', $fileNo)
                ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                ->where('status', 'registered')
                ->pluck('instrument_type')
                ->toArray();

            $hasSTAssignment = in_array('ST Assignment (Transfer of Title)', $registrations);
            $hasSectionalTitling = in_array('Sectional Titling CofO', $registrations);

            if ($hasSTAssignment && $hasSectionalTitling) {
                // Both types are registered, update the subapplication status
                $subApplication = DB::connection('sqlsrv')->table('subapplications')
                    ->where('fileno', $fileNo)
                    ->first();

                if ($subApplication) {
                    DB::connection('sqlsrv')->table('subapplications')
                        ->where('id', $subApplication->id)
                        ->update([
                            'deeds_status' => 'registered',
                            'updated_at' => now()
                        ]);

                    Log::info('Both ST Assignment and Sectional Titling registered', [
                        'file_no' => $fileNo,
                        'subapplication_id' => $subApplication->id
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error checking both types registered', [
                'file_no' => $fileNo,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update instrument completion status for ST Assignment and Sectional Titling CofO
     */
    private function updateInstrumentCompletionStatus($subApplicationId, $instrumentType, $status = 'Registered')
    {
        try {
            // Get current completion status
            $currentRecord = DB::connection('sqlsrv')->table('subapplications')
                ->where('id', $subApplicationId)
                ->select('deeds_completion_status')
                ->first();

            // Initialize or parse existing JSON
            $completionStatus = [
                'instruments' => [
                    [
                        'name' => 'ST Assignment (Transfer of Title)',
                        'status' => 'Pending'
                    ],
                    [
                        'name' => 'Sectional Titling CofO',
                        'status' => 'Pending'
                    ]
                ]
            ];

            if ($currentRecord && !empty($currentRecord->deeds_completion_status)) {
                $existingStatus = json_decode($currentRecord->deeds_completion_status, true);
                if ($existingStatus && isset($existingStatus['instruments'])) {
                    $completionStatus = $existingStatus;
                }
            }

            // Update the specific instrument status
            foreach ($completionStatus['instruments'] as &$instrument) {
                if ($instrument['name'] === $instrumentType) {
                    $instrument['status'] = $status;
                    break;
                }
            }

            // Update the database
            DB::connection('sqlsrv')->table('subapplications')
                ->where('id', $subApplicationId)
                ->update([
                    'deeds_completion_status' => json_encode($completionStatus),
                    'updated_at' => now(),
                    'updated_by' => Auth::id()
                ]);

            Log::info('Updated instrument completion status', [
                'subapplication_id' => $subApplicationId,
                'instrument_type' => $instrumentType,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating instrument completion status', [
                'subapplication_id' => $subApplicationId,
                'instrument_type' => $instrumentType,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Initialize default completion status for subapplications that don't have it set
     */
    private function initializeDefaultCompletionStatus()
    {
        try {
            // Get subapplications without deeds_completion_status
            $subapplicationsWithoutStatus = DB::connection('sqlsrv')->table('subapplications')
                ->where(function($query) {
                    $query->whereNull('deeds_completion_status')
                          ->orWhere('deeds_completion_status', '');
                })
                ->where('planning_recommendation_status', 'Approved')
                ->where('application_status', 'Approved')
                ->get();

            foreach ($subapplicationsWithoutStatus as $subApp) {
                $defaultStatus = [
                    'instruments' => [
                        [
                            'name' => 'ST Assignment (Transfer of Title)',
                            'status' => 'pending'
                        ],
                        [
                            'name' => 'Sectional Titling CofO',
                            'status' => 'pending'
                        ]
                    ]
                ];

                DB::connection('sqlsrv')->table('subapplications')
                    ->where('id', $subApp->id)
                    ->update([
                        'deeds_completion_status' => json_encode($defaultStatus),
                        'updated_at' => now()
                    ]);
            }

            if ($subapplicationsWithoutStatus->count() > 0) {
                Log::info('Initialized default completion status', [
                    'count' => $subapplicationsWithoutStatus->count()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error initializing default completion status', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Automatically register ST Fragmentation for approved mother applications
     * This function is called when the Instrument Registration page loads
     */
    private function autoRegisterSTFragmentation()
    {
        try {
            // Get approved mother applications that don't have ST Fragmentation registered yet
            $approvedMotherApplications = DB::connection('sqlsrv')->table('mother_applications as m')
                ->leftJoin('registered_instruments as ri', function($join) {
                    $join->on('m.fileno', '=', 'ri.MLSFileNo')
                         ->where('ri.instrument_type', '=', 'ST Fragmentation')
                         ->where('ri.status', '=', 'registered');
                })
                ->where('m.planning_recommendation_status', 'Approved')
                ->where('m.application_status', 'Approved')
                ->whereNull('ri.id') // Only get applications without existing ST Fragmentation
                ->select(
                    'm.id',
                    'm.fileno',
                    'm.np_fileno',
                    'm.applicant_title',
                    'm.first_name',
                    'm.middle_name',
                    'm.surname',
                    'm.corporate_name',
                    'm.rc_number',
                    'm.multiple_owners_names',
                    'm.owner_fullname as mother_applicant',
                    'm.property_lga as lga',
                    'm.property_district as district',
                    'm.plot_size as size',
                    'm.property_plot_no as plotNumber',
                    'm.property_house_no',
                    'm.property_street_name'
                )
                ->get();

            $registeredCount = 0;

            foreach ($approvedMotherApplications as $motherApp) {
                // Generate STM reference for ST Fragmentation
                $stmReference = $this->generateSTMReference();
                
                // Build mother applicant name properly for ST Fragmentation Grantee
                $motherApplicantParts = [];
                if (!empty($motherApp->applicant_title)) $motherApplicantParts[] = $motherApp->applicant_title;
                if (!empty($motherApp->first_name)) $motherApplicantParts[] = $motherApp->first_name;
                if (!empty($motherApp->middle_name)) $motherApplicantParts[] = $motherApp->middle_name;
                if (!empty($motherApp->surname)) $motherApplicantParts[] = $motherApp->surname;
                if (!empty($motherApp->corporate_name)) $motherApplicantParts[] = $motherApp->corporate_name;
                if (!empty($motherApp->rc_number)) $motherApplicantParts[] = $motherApp->rc_number;
                if (!empty($motherApp->multiple_owners_names)) $motherApplicantParts[] = $motherApp->multiple_owners_names;
                $motherApplicantName = implode(' ', $motherApplicantParts) ?: ($motherApp->mother_applicant ?? 'N/A');

                // Build property description
                $propertyParts = [];
                if (!empty($motherApp->property_house_no)) $propertyParts[] = 'House No: ' . $motherApp->property_house_no;
                if (!empty($motherApp->plotNumber)) $propertyParts[] = 'Plot No: ' . $motherApp->plotNumber;
                if (!empty($motherApp->property_street_name)) $propertyParts[] = $motherApp->property_street_name;
                if (!empty($motherApp->district)) $propertyParts[] = $motherApp->district;
                if (!empty($motherApp->lga)) $propertyParts[] = $motherApp->lga;
                $propertyDescription = implode(', ', $propertyParts) ?: 'Property details not available';

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
                    'parent_fileNo' => $motherApp->fileno, // fileno from mother_applications table as parent_fileNo
                    'fileNo' => $motherApp->np_fileno ?? $motherApp->fileno, // np_fileno from mother_applications table as fileNo
                    'StFileNo' => $motherApp->np_fileno ?? $motherApp->fileno,
                    'MLSFileNo' => $motherApp->fileno,
                    'lga' => $motherApp->lga ?? '',
                    'district' => $motherApp->district ?? '',
                    'size' => $motherApp->size ?? '',
                    'plotNumber' => $motherApp->plotNumber ?? '',
                    'propertyDescription' => $propertyDescription,
                    'created_by' => Auth::id() ?? 1, // Use current user or system user
                    'updated_by' => Auth::id() ?? 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // Insert into registered_instruments table
                DB::connection('sqlsrv')->table('registered_instruments')->insert($registrationData);
                $registeredCount++;

                Log::info('ST Fragmentation automatically registered', [
                    'application_id' => $motherApp->id,
                    'fileno' => $motherApp->fileno,
                    'np_fileno' => $motherApp->np_fileno ?? 'N/A',
                    'stm_ref' => $stmReference
                ]);
            }

            if ($registeredCount > 0) {
                Log::info('Auto-registered ST Fragmentation records', [
                    'count' => $registeredCount
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error auto-registering ST Fragmentation', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function getNextSerialNumber()
    {
        try {
            // Get the latest record by ordering by id DESC to get the most recently inserted record
            // This ensures we get the truly latest registration regardless of volume/page/serial values
            $latest = DB::connection('sqlsrv')->table('registered_instruments')
                ->select('volume_no', 'page_no', 'serial_no', 'id', 'created_at')
                ->whereNotNull('volume_no')
                ->whereNotNull('page_no') 
                ->whereNotNull('serial_no')
                ->where('volume_no', '>', 0) // Exclude ST Fragmentation records with 0/0/0
                ->where('page_no', '>', 0)
                ->where('serial_no', '>', 0)
                ->orderBy('id', 'desc') // Order by ID to get the most recently inserted record
                ->first();
            
            if (!$latest) {
                Log::info('getNextSerialNumber: no existing registered_instruments found, returning default 1/1/1');
                return response()->json([
                    'serial_no' => 1,
                    'page_no' => 1,
                    'volume_no' => 1,
                    'deeds_serial_no' => '1/1/1'
                ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                  ->header('Pragma', 'no-cache')
                  ->header('Expires', '0');
            }
            
            $volumeNo = $latest->volume_no; 
            $pageNo = $latest->page_no;
            $serialNo = $latest->serial_no;
            
            // Calculate next serial number
            if ($pageNo >= 100) {
                $volumeNo++;
                $pageNo = 1;
                $serialNo = 1;
            } else {
                $pageNo++;
                $serialNo++;
            }
            
            $deedsSerialNo = "$serialNo/$pageNo/$volumeNo";

            // Log the discovered latest row and the computed next serial for debugging
            Log::info('getNextSerialNumber: latest row', [
                'latest_id' => $latest->id ?? null,
                'latest_volume_no' => $latest->volume_no ?? null,
                'latest_page_no' => $latest->page_no ?? null,
                'latest_serial_no' => $latest->serial_no ?? null,
                'latest_created_at' => $latest->created_at ?? null
            ]);
            Log::info('getNextSerialNumber: computed next serial', [
                'serial_no' => $serialNo,
                'page_no' => $pageNo,
                'volume_no' => $volumeNo,
                'deeds_serial_no' => $deedsSerialNo
            ]);

            return response()->json([
                'serial_no' => $serialNo,
                'page_no' => $pageNo,
                'volume_no' => $volumeNo,
                'deeds_serial_no' => $deedsSerialNo
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('Error generating next serial number', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to generate serial number: ' . $e->getMessage()
            ], 500);
        }
    }

    public function registerBatch(Request $request)
    {
        try {
            $request->validate([
                'batch_entries' => 'required|array',
                'deeds_time' => 'required|string',
                'deeds_date' => 'required|date'
            ]);
            
            // Pre-validate ST Assignment and Sectional Titling entries
            $stFileValidation = [];
            foreach ($request->batch_entries as $entry) {
                $instrumentType = $entry['instrument_type'] ?? '';
                if (in_array($instrumentType, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])) {
                    $fileNo = $entry['file_no'] ?? '';
                    if (empty($fileNo)) {
                        return response()->json([
                            'success' => false, 
                            'error' => "File number (StFileNo) is required for {$instrumentType}"
                        ], 422);
                    }
                    
                    // Track what we're trying to register for each file
                    if (!isset($stFileValidation[$fileNo])) {
                        $stFileValidation[$fileNo] = [];
                    }
                    $stFileValidation[$fileNo][] = $instrumentType;
                }
            }
            
            // Check for existing registrations and duplicates within the batch
            foreach ($stFileValidation as $fileNo => $types) {
                // Check for duplicates within the batch
                if (count($types) !== count(array_unique($types))) {
                    return response()->json([
                        'success' => false, 
                        'error' => "Duplicate instrument types found in batch for file number {$fileNo}"
                    ], 422);
                }
                
                // Check existing registrations in database
                $existingRegistrations = DB::connection('sqlsrv')->table('registered_instruments')
                    ->where('StFileNo', $fileNo)
                    ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                    ->pluck('instrument_type')
                    ->toArray();
                
                foreach ($types as $type) {
                    if (in_array($type, $existingRegistrations)) {
                        return response()->json([
                            'success' => false, 
                            'error' => "A {$type} registration already exists for file number {$fileNo}"
                        ], 422);
                    }
                }
            }
            
            $serialData = $this->getNextSerialNumber()->getData(true);
            $results = [];
            $processedRecords = [];
            $registeredFiles = []; // Track files for final validation
            
            DB::connection('sqlsrv')->beginTransaction();
            
            foreach ($request->batch_entries as $index => $entry) {
                if ($index > 0) {
                    if (++$serialData['page_no'] > 100) {
                        $serialData['volume_no']++;
                        $serialData['page_no'] = 1;
                        $serialData['serial_no'] = 1;
                    } else {
                        $serialData['serial_no']++;
                    }
                    $serialData['deeds_serial_no'] = "{$serialData['serial_no']}/{$serialData['page_no']}/{$serialData['volume_no']}";
                }
                
                $applicationId = $entry['application_id'];
                $sourceRecord = null;
                $sourceTable = null;
                
                // Handle composite IDs for ST Assignment and Sectional Titling
                if (strpos($applicationId, '_st_assignment') !== false || strpos($applicationId, '_sectional_cofo') !== false) {
                    $originalId = str_replace(['_st_assignment', '_sectional_cofo'], '', $applicationId);
                    $sourceRecord = DB::connection('sqlsrv')->table('subapplications')->where('id', $originalId)->first();
                    if ($sourceRecord) {
                        $sourceTable = 'subapplications';
                        $sourceRecord->original_id = $originalId;
                    }
                } elseif (strpos($applicationId, 'instr_reg_') === 0) {
                    // Handle instrument_registration IDs that start with 'instr_reg_'
                    $originalId = str_replace('instr_reg_', '', $applicationId);
                    $sourceRecord = DB::connection('sqlsrv')->table('instrument_registration')->where('id', $originalId)->first();
                    if ($sourceRecord) {
                        $sourceTable = 'instrument_registration';
                    }
                } else {
                    // Only check subapplications if the ID is numeric
                    if (is_numeric($applicationId)) {
                        $sourceRecord = DB::connection('sqlsrv')->table('subapplications')->where('id', $applicationId)->first();
                        if ($sourceRecord) {
                            $sourceTable = 'subapplications';
                        }
                    }
                    
                    if (!isset($sourceRecord)) {
                        $sourceRecord = DB::connection('sqlsrv')->table('instrument_registration')->where('id', $applicationId)->first();
                        if ($sourceRecord) {
                            $sourceTable = 'instrument_registration';
                        } else {
                            $sourceRecord = DB::connection('sqlsrv')->table('mother_applications')->where('id', $applicationId)->first();
                            if ($sourceRecord) {
                                $sourceTable = 'mother_applications';
                            }
                        }
                    }
                }
                    
                if (!$sourceRecord) {
                    Log::warning('Source record not found for batch entry', ['application_id' => $applicationId]);
                    continue;
                }
                
                // Update ID handling for instrument_registration IDs
                $updateId = isset($sourceRecord->original_id) ? $sourceRecord->original_id : $applicationId;
                
                // For instrument_registration IDs, we need to extract the numeric part
                if ($sourceTable === 'instrument_registration' && strpos($updateId, 'instr_reg_') === 0) {
                    $updateId = str_replace('instr_reg_', '', $updateId);
                }
                
                $processedRecords[] = ['id' => $updateId, 'table' => $sourceTable];
                $stmReference = $this->generateSTMReference();
                
                $entryRequest = new \Illuminate\Http\Request();
                $entryRequest->merge([
                    'instrument_type' => $entry['instrument_type'] ?? '',
                    'Grantor' => $entry['grantor'] ?? '',
                    'Grantee' => $entry['grantee'] ?? '',
                    'duration' => $entry['duration'] ?? '',
                    'propertyDescription' => $entry['propertyDescription'] ?? '',
                    'lga' => $entry['lga'] ?? '',
                    'district' => $entry['district'] ?? '',
                    'plotSize' => $entry['size'] ?? '',
                    'plotNumber' => $entry['plotNumber'] ?? '',
                    'deeds_date' => $request->deeds_date,
                    'deeds_time' => $request->deeds_time,
                    'file_no' => $entry['file_no'] ?? ''
                ]);
                
                $dataToInsert = $this->prepareRegistrationData($sourceRecord, $sourceTable, $entryRequest, $serialData, $stmReference);
                $newId = DB::connection('sqlsrv')->table('registered_instruments')->insertGetId($dataToInsert);
                
                // Update instrument completion status for ST Assignment and Sectional Titling
                $instrumentType = $entry['instrument_type'] ?? '';
                if (in_array($instrumentType, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO']) && $sourceTable === 'subapplications') {
                    $this->updateInstrumentCompletionStatus($updateId, $instrumentType, 'Registered');
                }
                
                // Track registered files for final validation
                if (in_array($instrumentType, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])) {
                    $fileNo = $entry['file_no'] ?? $sourceRecord->fileno;
                    $registeredFiles[] = $fileNo;
                }
                
                $results[] = [
                    'application_id' => $applicationId,
                    'new_id' => $newId,
                    'deeds_serial_no' => $serialData['deeds_serial_no'],
                    'stm_ref' => $stmReference,
                    'source_table' => $sourceTable
                ];
            }
            
            foreach ($processedRecords as $record) {
                $this->updateSourceRecordStatus($record['id'], $record['table']);
            }
            
            // Check if both types are registered for each file
            foreach (array_unique($registeredFiles) as $fileNo) {
                $this->checkBothTypesRegistered($fileNo);
            }
            
            DB::connection('sqlsrv')->commit();
            
            return response()->json(['success' => true, 'message' => count($results) . ' instruments registered successfully', 'results' => $results]);
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            Log::error('Error in registerBatch', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => 'Failed to register batch: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check registration status for ST Assignment and Sectional Titling CofO for a given file number
     */
    public function checkRegistrationStatus(Request $request)
    {
        try {
            $fileNo = $request->query('file_no');
            
            if (empty($fileNo)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File number is required'
                ], 400);
            }

            $registrations = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('StFileNo', $fileNo)
                ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                ->select('instrument_type', 'status', 'particularsRegistrationNumber', 'STM_Ref', 'created_at')
                ->get();

            $stAssignment = $registrations->firstWhere('instrument_type', 'ST Assignment (Transfer of Title)');
            $sectionalTitling = $registrations->firstWhere('instrument_type', 'Sectional Titling CofO');

            $response = [
                'success' => true,
                'file_no' => $fileNo,
                'st_assignment' => [
                    'registered' => !is_null($stAssignment),
                    'status' => $stAssignment->status ?? null,
                    'registration_number' => $stAssignment->particularsRegistrationNumber ?? null,
                    'stm_ref' => $stAssignment->STM_Ref ?? null,
                    'registered_date' => $stAssignment->created_at ?? null
                ],
                'sectional_titling' => [
                    'registered' => !is_null($sectionalTitling),
                    'status' => $sectionalTitling->status ?? null,
                    'registration_number' => $sectionalTitling->particularsRegistrationNumber ?? null,
                    'stm_ref' => $sectionalTitling->STM_Ref ?? null,
                    'registered_date' => $sectionalTitling->created_at ?? null
                ],
                'both_registered' => !is_null($stAssignment) && !is_null($sectionalTitling),
                'total_registrations' => $registrations->count()
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error checking registration status', [
                'file_no' => $request->query('file_no'),
                'exception' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to check registration status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getBatchData(Request $request)
    {
        try {
            $filter = $request->query('filter', 'batch');
            $data = collect();
            
            switch ($filter) {
                case 'other':
                    // FIXED: Only return pending Other instruments
                    $data = DB::connection('sqlsrv')->table('instrument_registration')
                        ->where('status', 'pending')
                        ->whereNull('particularsRegistrationNumber') // Exclude instruments with registration numbers
                        ->whereNull('STM_Ref') // Exclude instruments with STM references
                        ->select(
                            'id', 
                            DB::raw("COALESCE(MLSFileNo, KAGISFileNO, NewKANGISFileNo) as fileno"), 
                            'instrument_type', 
                            'Grantor as grantor', 
                            'Grantee as grantee', 
                            'lga', 
                            'district', 
                            'size', 
                            'plotNumber', 
                            'created_at',
                            DB::raw("'pending' as status"),
                            DB::raw("'Other Instruments' as source_type")
                        )
                        ->get();
                    break;
                    
                case 'stAssignment':
                    // FIXED: Only return pending ST Assignment instruments
                    $approvedSubapplications = DB::connection('sqlsrv')->table('subapplications as s')
                        ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
                        ->leftJoin('registered_instruments as ri', function($join) {
                            $join->on('s.fileno', '=', 'ri.StFileNo')
                                 ->where('ri.instrument_type', '=', 'ST Assignment (Transfer of Title)')
                                 ->where('ri.status', '=', 'registered');
                        })
                        ->where('s.planning_recommendation_status', 'Approved')
                        ->where('s.application_status', 'Approved')
                        ->whereNull('ri.id') // Only get subapplications without existing ST Assignment registration
                        ->select(
                            's.id',
                            's.fileno',
                            's.deeds_completion_status',
                            DB::raw("CONCAT(COALESCE(s.applicant_title,''), ' ', COALESCE(s.first_name,''), ' ', COALESCE(s.surname,''), COALESCE(s.corporate_name,''), COALESCE(s.multiple_owners_names,'')) as sub_applicant"),
                            DB::raw("CONCAT(COALESCE(m.applicant_title,''), ' ', COALESCE(m.first_name,''), ' ', COALESCE(m.surname,''), COALESCE(m.corporate_name,''), COALESCE(m.multiple_owners_names,'')) as mother_applicant"),
                            'm.property_lga as lga', 
                            'm.property_district as district', 
                            'm.plot_size as size', 
                            'm.property_plot_no as plotNumber', 
                            's.created_at'
                        )
                        ->get();
                    
                    // Create ST Assignment records for each pending subapplication
                    $data = collect();
                    foreach ($approvedSubapplications as $subApp) {
                        $data->push((object)[
                            'id' => $subApp->id . '_st_assignment',
                            'fileno' => $subApp->fileno,
                            'instrument_type' => 'ST Assignment (Transfer of Title)',
                            'grantor' => $subApp->mother_applicant,
                            'grantee' => $subApp->sub_applicant,
                            'lga' => $subApp->lga,
                            'district' => $subApp->district,
                            'size' => $subApp->size,
                            'plotNumber' => $subApp->plotNumber,
                            'created_at' => $subApp->created_at,
                            'status' => 'pending',
                            'source_type' => 'ST Assignment',
                            'original_subapp_id' => $subApp->id
                        ]);
                    }
                    break;
                    
                case 'regular':
                case 'sltr':
                    // Keep these available for other instrument types in modals
                    $data = collect([
                        (object)[
                            'id' => null,
                            'fileno' => 'No Record',
                            'grantor' => 'No Record',
                            'grantee' => 'No Record',
                            'lga' => 'No Record',
                            'district' => 'No Record',
                            'size' => 'No Record',
                            'plotNumber' => 'No Record',
                            'created_at' => null,
                            'status' => 'unavailable'
                        ]
                    ]);
                    break;
                    
                case 'sectional':
                    // FIXED: Only return pending Sectional Titling instruments
                    $approvedSubapplications = DB::connection('sqlsrv')->table('subapplications as s')
                        ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
                        ->leftJoin('registered_instruments as ri', function($join) {
                            $join->on('s.fileno', '=', 'ri.StFileNo')
                                 ->where('ri.instrument_type', '=', 'Sectional Titling CofO')
                                 ->where('ri.status', '=', 'registered');
                        })
                        ->where('s.planning_recommendation_status', 'Approved')
                        ->where('s.application_status', 'Approved')
                        ->whereNull('ri.id') // Only get subapplications without existing Sectional Titling registration
                        ->select(
                            's.id',
                            's.fileno',
                            's.deeds_completion_status',
                            DB::raw("CONCAT(COALESCE(s.applicant_title,''), ' ', COALESCE(s.first_name,''), ' ', COALESCE(s.surname,''), COALESCE(s.corporate_name,''), COALESCE(s.multiple_owners_names,'')) as sub_applicant"),
                            DB::raw("CONCAT(COALESCE(m.applicant_title,''), ' ', COALESCE(m.first_name,''), ' ', COALESCE(m.surname,''), COALESCE(m.corporate_name,''), COALESCE(m.multiple_owners_names,'')) as mother_applicant"),
                            'm.property_lga as lga', 
                            'm.property_district as district', 
                            'm.plot_size as size', 
                            'm.property_plot_no as plotNumber', 
                            's.created_at'
                        )
                        ->get();
                    
                    // Create Sectional Titling records for each pending subapplication
                    $data = collect();
                    foreach ($approvedSubapplications as $subApp) {
                        $data->push((object)[
                            'id' => $subApp->id . '_sectional_cofo',
                            'fileno' => $subApp->fileno,
                            'instrument_type' => 'Sectional Titling CofO',
                            'grantor' => 'Kano State Government',
                            'grantee' => $subApp->sub_applicant,
                            'lga' => $subApp->lga,
                            'district' => $subApp->district,
                            'size' => $subApp->size,
                            'plotNumber' => $subApp->plotNumber,
                            'created_at' => $subApp->created_at,
                            'status' => 'pending',
                            'source_type' => 'Sectional Titling',
                            'original_subapp_id' => $subApp->id
                        ]);
                    }
                    break;
                    
                case 'batch':
                default:
                    // FIXED: For batch registration, only include pending instruments
                    
                    // Get pending other instruments
                    $instrumentData = DB::connection('sqlsrv')->table('instrument_registration')
                        ->where('status', 'pending')
                        ->whereNull('particularsRegistrationNumber') // Exclude instruments with registration numbers
                        ->whereNull('STM_Ref') // Exclude instruments with STM references
                        ->select(
                            'id', 
                            DB::raw("COALESCE(MLSFileNo, KAGISFileNO, NewKANGISFileNo) as fileno"), 
                            'instrument_type', 
                            'Grantor as grantor', 
                            'Grantee as grantee', 
                            'lga', 
                            'district', 
                            'size', 
                            'plotNumber', 
                            'created_at', 
                            DB::raw("'pending' as status"), 
                            DB::raw("'Other Instruments' as source_type")
                        )
                        ->get();
                    
                    // Get pending ST Assignment instruments
                    $pendingSTAssignments = DB::connection('sqlsrv')->table('subapplications as s')
                        ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
                        ->leftJoin('registered_instruments as ri', function($join) {
                            $join->on('s.fileno', '=', 'ri.StFileNo')
                                 ->where('ri.instrument_type', '=', 'ST Assignment (Transfer of Title)')
                                 ->where('ri.status', '=', 'registered');
                        })
                        ->where('s.planning_recommendation_status', 'Approved')
                        ->where('s.application_status', 'Approved')
                        ->whereNull('ri.id') // Only get subapplications without existing ST Assignment registration
                        ->select(
                            's.id',
                            's.fileno',
                            's.deeds_completion_status',
                            DB::raw("CONCAT(COALESCE(s.applicant_title,''), ' ', COALESCE(s.first_name,''), ' ', COALESCE(s.surname,''), COALESCE(s.corporate_name,''), COALESCE(s.multiple_owners_names,'')) as sub_applicant"),
                            DB::raw("CONCAT(COALESCE(m.applicant_title,''), ' ', COALESCE(m.first_name,''), ' ', COALESCE(m.surname,''), COALESCE(m.corporate_name,''), COALESCE(m.multiple_owners_names,'')) as mother_applicant"),
                            'm.property_lga as lga', 
                            'm.property_district as district', 
                            'm.plot_size as size', 
                            'm.property_plot_no as plotNumber', 
                            's.created_at'
                        )
                        ->get();
                    
                    // Get pending Sectional Titling instruments
                    $pendingSectionalTitling = DB::connection('sqlsrv')->table('subapplications as s')
                        ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
                        ->leftJoin('registered_instruments as ri', function($join) {
                            $join->on('s.fileno', '=', 'ri.StFileNo')
                                 ->where('ri.instrument_type', '=', 'Sectional Titling CofO')
                                 ->where('ri.status', '=', 'registered');
                        })
                        ->where('s.planning_recommendation_status', 'Approved')
                        ->where('s.application_status', 'Approved')
                        ->whereNull('ri.id') // Only get subapplications without existing Sectional Titling registration
                        ->select(
                            's.id',
                            's.fileno',
                            's.deeds_completion_status',
                            DB::raw("CONCAT(COALESCE(s.applicant_title,''), ' ', COALESCE(s.first_name,''), ' ', COALESCE(s.surname,''), COALESCE(s.corporate_name,''), COALESCE(s.multiple_owners_names,'')) as sub_applicant"),
                            DB::raw("CONCAT(COALESCE(m.applicant_title,''), ' ', COALESCE(m.first_name,''), ' ', COALESCE(m.surname,''), COALESCE(m.corporate_name,''), COALESCE(m.multiple_owners_names,'')) as mother_applicant"),
                            'm.property_lga as lga', 
                            'm.property_district as district', 
                            'm.plot_size as size', 
                            'm.property_plot_no as plotNumber', 
                            's.created_at'
                        )
                        ->get();
                    
                    // Create collections for ST Assignment and Sectional Titling data
                    $stAssignmentData = collect();
                    $sectionalTitlingData = collect();
                    
                    foreach ($pendingSTAssignments as $subApp) {
                        $stAssignmentData->push((object)[
                            'id' => $subApp->id . '_st_assignment',
                            'fileno' => $subApp->fileno,
                            'instrument_type' => 'ST Assignment (Transfer of Title)',
                            'grantor' => $subApp->mother_applicant,
                            'grantee' => $subApp->sub_applicant,
                            'lga' => $subApp->lga,
                            'district' => $subApp->district,
                            'size' => $subApp->size,
                            'plotNumber' => $subApp->plotNumber,
                            'created_at' => $subApp->created_at,
                            'status' => 'pending',
                            'source_type' => 'ST Assignment',
                            'original_subapp_id' => $subApp->id
                        ]);
                    }
                    
                    foreach ($pendingSectionalTitling as $subApp) {
                        $sectionalTitlingData->push((object)[
                            'id' => $subApp->id . '_sectional_cofo',
                            'fileno' => $subApp->fileno,
                            'instrument_type' => 'Sectional Titling CofO',
                            'grantor' => 'Kano State Government',
                            'grantee' => $subApp->sub_applicant,
                            'lga' => $subApp->lga,
                            'district' => $subApp->district,
                            'size' => $subApp->size,
                            'plotNumber' => $subApp->plotNumber,
                            'created_at' => $subApp->created_at,
                            'status' => 'pending',
                            'source_type' => 'Sectional Titling',
                            'original_subapp_id' => $subApp->id
                        ]);
                    }
                    
                    $data = $instrumentData->merge($stAssignmentData)->merge($sectionalTitlingData);
                    break;
            }
            
            // FINAL FILTER: Ensure all returned data has 'pending' status
            $data = $data->filter(function($item) {
                return $item->status === 'pending';
            });
            
            return response()->json($data->values()->toArray());
            
        } catch (\Exception $e) {
            Log::error('Error in getBatchData', ['filter' => $request->query('filter'), 'exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Failed to fetch batch data: ' . $e->getMessage()], 500);
        }
    }

    public function declineRegistration(Request $request)
    {
        try {
            $request->validate([
                'application_id' => 'required',
                'decline_reason' => 'required|string|max:500'
            ]);

            $applicationId = $request->application_id;
            $sourceRecord = null;
            $sourceTable = null;
            
            // Handle composite IDs for ST Assignment and Sectional Titling
            if (strpos($applicationId, '_st_assignment') !== false || strpos($applicationId, '_sectional_cofo') !== false) {
                $originalId = str_replace(['_st_assignment', '_sectional_cofo'], '', $applicationId);
                $sourceRecord = DB::connection('sqlsrv')->table('subapplications')->where('id', $originalId)->first();
                if ($sourceRecord) {
                    $sourceTable = 'subapplications';
                    $sourceRecord->original_id = $originalId;
                }
            } elseif (strpos($applicationId, 'instr_reg_') === 0) {
                // Handle instrument_registration IDs that start with 'instr_reg_'
                $originalId = str_replace('instr_reg_', '', $applicationId);
                $sourceRecord = DB::connection('sqlsrv')->table('instrument_registration')->where('id', $originalId)->first();
                if ($sourceRecord) {
                    $sourceTable = 'instrument_registration';
                }
            } else {
                // Only check subapplications if the ID is numeric
                if (is_numeric($applicationId)) {
                    $sourceRecord = DB::connection('sqlsrv')->table('subapplications')->where('id', $applicationId)->first();
                    if ($sourceRecord) {
                        $sourceTable = 'subapplications';
                    }
                }
                
                if (!isset($sourceRecord)) {
                    $sourceRecord = DB::connection('sqlsrv')->table('instrument_registration')->where('id', $applicationId)->first();
                    if ($sourceRecord) {
                        $sourceTable = 'instrument_registration';
                    } else {
                        $sourceRecord = DB::connection('sqlsrv')->table('mother_applications')->where('id', $applicationId)->first();
                        if ($sourceRecord) {
                            $sourceTable = 'mother_applications';
                        }
                    }
                }
            }
                
            if (!$sourceRecord) {
                return response()->json(['success' => false, 'error' => 'Source record not found'], 404);
            }
            
            // Update status using original ID if it's a composite ID
            $updateId = isset($sourceRecord->original_id) ? $sourceRecord->original_id : $applicationId;
            
            // For instrument_registration IDs, we need to extract the numeric part
            if ($sourceTable === 'instrument_registration' && strpos($updateId, 'instr_reg_') === 0) {
                $updateId = str_replace('instr_reg_', '', $updateId);
            }
            
            $updateData = [
                'deeds_status' => 'declined',
                'decline_reason' => $request->decline_reason,
                'updated_by' => Auth::id(),
                'updated_at' => now()
            ];

            switch ($sourceTable) {
                case 'instrument_registration':
                    $updateData['status'] = 'declined';
                    DB::connection('sqlsrv')->table('instrument_registration')->where('id', $updateId)->update($updateData);
                    break;

                case 'mother_applications':
                    DB::connection('sqlsrv')->table('mother_applications')->where('id', $updateId)->update($updateData);
                    break;

                case 'subapplications':
                    DB::connection('sqlsrv')->table('subapplications')->where('id', $updateId)->update($updateData);
                    break;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Registration declined successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in declineRegistration', ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'error' => 'Failed to decline registration: ' . $e->getMessage()], 500);
        }
    }

    public function getOverallCompletionStatus(Request $request)
    {
        try {
            // Get counts for different statuses
            $totalInstruments = DB::connection('sqlsrv')->table('subapplications')
                ->where('planning_recommendation_status', 'Approved')
                ->where('application_status', 'Approved')
                ->count() * 2; // Each subapplication has 2 instruments (ST Assignment + Sectional Titling)
            
            $registeredInstruments = DB::connection('sqlsrv')->table('registered_instruments')
                ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                ->where('status', 'registered')
                ->count();
            
            $pendingInstruments = $totalInstruments - $registeredInstruments;
            
            $completionPercentage = $totalInstruments > 0 ? round(($registeredInstruments / $totalInstruments) * 100, 2) : 0;
            
            return response()->json([
                'success' => true,
                'total_instruments' => $totalInstruments,
                'registered_instruments' => $registeredInstruments,
                'pending_instruments' => $pendingInstruments,
                'completion_percentage' => $completionPercentage
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting overall completion status', [
                'exception' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get completion status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getFileCompletionStatus(Request $request)
    {
        try {
            $fileNo = $request->query('file_no');
            
            if (empty($fileNo)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File number is required'
                ], 400);
            }

            // Get the subapplication for this file number
            $subApplication = DB::connection('sqlsrv')->table('subapplications')
                ->where('fileno', $fileNo)
                ->first();

            if (!$subApplication) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found'
                ], 404);
            }

            // Check registration status for both instruments
            $registrations = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('StFileNo', $fileNo)
                ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                ->select('instrument_type', 'status')
                ->get();

            $stAssignmentRegistered = $registrations->contains('instrument_type', 'ST Assignment (Transfer of Title)');
            $sectionalTitlingRegistered = $registrations->contains('instrument_type', 'Sectional Titling CofO');
            
            $completionStatus = [
                'instruments' => [
                    [
                        'name' => 'ST Assignment (Transfer of Title)',
                        'status' => $stAssignmentRegistered ? 'Registered' : 'Pending'
                    ],
                    [
                        'name' => 'Sectional Titling CofO',
                        'status' => $sectionalTitlingRegistered ? 'Registered' : 'Pending'
                    ]
                ]
            ];

            $overallStatus = ($stAssignmentRegistered && $sectionalTitlingRegistered) ? 'Completed' : 'In Progress';
            $completionPercentage = (($stAssignmentRegistered ? 1 : 0) + ($sectionalTitlingRegistered ? 1 : 0)) / 2 * 100;

            return response()->json([
                'success' => true,
                'file_no' => $fileNo,
                'overall_status' => $overallStatus,
                'completion_percentage' => $completionPercentage,
                'completion_details' => $completionStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting file completion status', [
                'file_no' => $request->query('file_no'),
                'exception' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get file completion status: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateSourceRecordStatus($id, $sourceTable)
    {
        $updateData = [
            'updated_by' => Auth::id(),
            'updated_at' => now()
        ];

        switch ($sourceTable) {
            case 'instrument_registration':
                $updateData['status'] = 'registered';
                DB::connection('sqlsrv')->table('instrument_registration')->where('id', $id)->update($updateData);
                break;

            case 'mother_applications':
                $updateData['deeds_status'] = 'registered';
                DB::connection('sqlsrv')->table('mother_applications')->where('id', $id)->update($updateData);
                break;

            case 'subapplications':
                $updateData['deeds_status'] = 'registered';
                DB::connection('sqlsrv')->table('subapplications')->where('id', $id)->update($updateData);
                break;
        }
    }
}