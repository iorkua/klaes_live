<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Fixed batch data route
Route::get('/instrument_registration/get-batch-data-fixed', function (Request $request) {
    try {
        $filter = $request->query('filter', 'batch');
        $data = collect();
        
        switch ($filter) {
            case 'other':
                // FIXED: Only return pending Other instruments
                $data = DB::connection('sqlsrv')->table('instrument_registration')
                    ->where(function ($q) {
                        $q->where('status', '!=', 'registered')
                          ->orWhereNull('status');
                    })
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
                
            case 'sectional':
                // FIXED: Only return pending Sectional Titling CofO instruments
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
                
                // Create Sectional Titling CofO records for each pending subapplication
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
                
            case 'regular':
                // Regular CofO instruments - return empty for now as they're not implemented
                $data = collect();
                break;
                
            case 'sltr':
                // SLTR CofO instruments - return empty for now as they're not implemented
                $data = collect();
                break;
                
            case 'batch':
            default:
                // Return all pending instruments for batch registration
                
                // Get pending Other instruments
                $otherInstruments = DB::connection('sqlsrv')->table('instrument_registration')
                    ->where(function ($q) {
                        $q->where('status', '!=', 'registered')
                          ->orWhereNull('status');
                    })
                    ->whereNull('particularsRegistrationNumber')
                    ->whereNull('STM_Ref')
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
                $stAssignmentSubapps = DB::connection('sqlsrv')->table('subapplications as s')
                    ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
                    ->leftJoin('registered_instruments as ri', function($join) {
                        $join->on('s.fileno', '=', 'ri.StFileNo')
                             ->where('ri.instrument_type', '=', 'ST Assignment (Transfer of Title)')
                             ->where('ri.status', '=', 'registered');
                    })
                    ->where('s.planning_recommendation_status', 'Approved')
                    ->where('s.application_status', 'Approved')
                    ->whereNull('ri.id')
                    ->select(
                        's.id',
                        's.fileno',
                        DB::raw("CONCAT(COALESCE(s.applicant_title,''), ' ', COALESCE(s.first_name,''), ' ', COALESCE(s.surname,''), COALESCE(s.corporate_name,''), COALESCE(s.multiple_owners_names,'')) as sub_applicant"),
                        DB::raw("CONCAT(COALESCE(m.applicant_title,''), ' ', COALESCE(m.first_name,''), ' ', COALESCE(m.surname,''), COALESCE(m.corporate_name,''), COALESCE(m.multiple_owners_names,'')) as mother_applicant"),
                        'm.property_lga as lga', 
                        'm.property_district as district', 
                        'm.plot_size as size', 
                        'm.property_plot_no as plotNumber', 
                        's.created_at'
                    )
                    ->get();
                
                // Get pending Sectional Titling CofO instruments
                $sectionalSubapps = DB::connection('sqlsrv')->table('subapplications as s')
                    ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
                    ->leftJoin('registered_instruments as ri', function($join) {
                        $join->on('s.fileno', '=', 'ri.StFileNo')
                             ->where('ri.instrument_type', '=', 'Sectional Titling CofO')
                             ->where('ri.status', '=', 'registered');
                    })
                    ->where('s.planning_recommendation_status', 'Approved')
                    ->where('s.application_status', 'Approved')
                    ->whereNull('ri.id')
                    ->select(
                        's.id',
                        's.fileno',
                        DB::raw("CONCAT(COALESCE(s.applicant_title,''), ' ', COALESCE(s.first_name,''), ' ', COALESCE(s.surname,''), COALESCE(s.corporate_name,''), COALESCE(s.multiple_owners_names,'')) as sub_applicant"),
                        DB::raw("CONCAT(COALESCE(m.applicant_title,''), ' ', COALESCE(m.first_name,''), ' ', COALESCE(m.surname,''), COALESCE(m.corporate_name,''), COALESCE(m.multiple_owners_names,'')) as mother_applicant"),
                        'm.property_lga as lga', 
                        'm.property_district as district', 
                        'm.plot_size as size', 
                        'm.property_plot_no as plotNumber', 
                        's.created_at'
                    )
                    ->get();
                
                $data = collect();
                
                // Add other instruments
                foreach ($otherInstruments as $instrument) {
                    $data->push((object)[
                        'id' => 'instr_reg_' . $instrument->id,
                        'fileno' => $instrument->fileno,
                        'instrument_type' => $instrument->instrument_type,
                        'grantor' => $instrument->grantor,
                        'grantee' => $instrument->grantee,
                        'lga' => $instrument->lga,
                        'district' => $instrument->district,
                        'size' => $instrument->size,
                        'plotNumber' => $instrument->plotNumber,
                        'created_at' => $instrument->created_at,
                        'status' => 'pending',
                        'source_type' => 'Other Instruments'
                    ]);
                }
                
                // Add ST Assignment instruments
                foreach ($stAssignmentSubapps as $subApp) {
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
                        'source_type' => 'ST Assignment'
                    ]);
                }
                
                // Add Sectional Titling CofO instruments
                foreach ($sectionalSubapps as $subApp) {
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
                        'source_type' => 'Sectional Titling'
                    ]);
                }
                break;
        }
        
        Log::info('Fixed batch data retrieved', [
            'filter' => $filter,
            'count' => $data->count(),
            'sample_data' => $data->take(3)->toArray()
        ]);
        
        return response()->json($data->toArray());
        
    } catch (\Exception $e) {
        Log::error('Error in fixed getBatchData', [
            'filter' => $request->query('filter'),
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => 'Failed to retrieve batch data: ' . $e->getMessage()
        ], 500);
    }
});