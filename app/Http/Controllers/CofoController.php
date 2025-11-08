<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CofoController  extends Controller
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

    public function CertificateOfOccupancy()
    {
        $PageTitle = 'ST Certificate of Occupancy';
        $PageDescription = '';

        // Fetch all unit applications with st_cofo data
        $approvedUnitApplications = DB::connection('sqlsrv')->table('subapplications')
            ->join('mother_applications', 'subapplications.main_application_id', '=', 'mother_applications.id')
            ->leftJoin('st_cofo', function($join) {
                $join->on('subapplications.id', '=', 'st_cofo.sub_application_id')
                     ->where('st_cofo.is_active', 1);
            })
            ->leftJoin('SectionalCofOReg', 'subapplications.id', '=', 'SectionalCofOReg.sub_application_id')
            ->where('subapplications.planning_recommendation_status', 'Approved')
            ->where('subapplications.application_status', 'Approved')
            ->select(
                'subapplications.id',
                'subapplications.fileno',
                'subapplications.applicant_type',
                'subapplications.applicant_title',
                'subapplications.first_name',
                'subapplications.surname',
                'subapplications.corporate_name',
                'subapplications.multiple_owners_names',
                'subapplications.block_number',
                'subapplications.floor_number',
                'subapplications.unit_number',
                'subapplications.application_status',
                'subapplications.scheme_no',
                'subapplications.planning_recommendation_status',
                'subapplications.main_application_id',
                'mother_applications.property_lga',
                'mother_applications.land_use',
                'st_cofo.certificate_number',
                'SectionalCofOReg.Deeds_Serial_No'
            )
            ->get();

        // Process owner names
        foreach ($approvedUnitApplications as $application) {
            if (!empty($application->multiple_owners_names)) {
                $ownerArray = json_decode($application->multiple_owners_names, true);
                $application->owner_name = $ownerArray ? implode(', ', $ownerArray) : null;
            } elseif (!empty($application->corporate_name)) {
                $application->owner_name = $application->corporate_name;
            } else {
                $application->owner_name = trim($application->applicant_title . ' ' . $application->first_name . ' ' . $application->surname);
            }
            
            // Check if a certificate number exists to determine if certificate is issued
            $application->certificate_issued = !empty($application->certificate_number);
        }

        return view('programmes.certificates', compact(
            'approvedUnitApplications',
            'PageTitle',
            'PageDescription'
        ));
    }

    /**
     * Generate the next available certificate number
     * 
     * @return string
     */
    private function generateNextCertificateNumber()
    {
        $year = date('Y');
        $lastCofO = DB::connection('sqlsrv')->table('st_cofo')
            ->where('certificate_number', 'like', "ST/COFO/$year/%")
            ->orderBy('id', 'desc')
            ->first();
            
        $lastNumber = 0;
        if ($lastCofO) {
            $parts = explode('/', $lastCofO->certificate_number);
            $lastNumber = (int)end($parts);
        }
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        return "ST/COFO/$year/$newNumber";
    }

    // Generate CofO form
    public function generateCofO($id)
    {
        $PageTitle = 'Generate Certificate of Occupancy';
        $PageDescription = 'Create a new Certificate of Occupancy for sectional title';
        
        try {
            // Fetch the subapplication details with mother application data
            $application = DB::connection('sqlsrv')->table('subapplications')
                ->leftJoin('mother_applications', 'subapplications.main_application_id', '=', 'mother_applications.id')
                ->where('subapplications.id', $id)
                ->select(
                    'subapplications.*',
                    'mother_applications.id as mother_id',
                    'mother_applications.property_house_no',
                    'mother_applications.property_plot_no',
                    'mother_applications.property_street_name',
                    'mother_applications.property_district',
                    'mother_applications.property_lga',
                    'mother_applications.property_state',
                    'mother_applications.land_use'
                )
                ->first();

            if (!$application) {
                return back()->with('error', 'Application not found');
            }

            // Check prerequisites before allowing CofO generation
            $validationErrors = [];
            
            // Debug: Log the application details
            \Log::info('Checking prerequisites for application ID: ' . $application->id);
            \Log::info('Main application ID: ' . ($application->main_application_id ?? 'NULL'));
            \Log::info('Mother ID: ' . ($application->mother_id ?? 'NULL'));
            
            // Check if ST Memo has been generated (ST Memo is linked to the main application)
            $memoApplicationId = $application->main_application_id ?? $application->mother_id;
            \Log::info('Looking for memo with application_id: ' . $memoApplicationId);
            
            $hasSTMemo = DB::connection('sqlsrv')->table('memos')
                ->where('application_id', $memoApplicationId)
                ->exists(); // Remove the memo_status check for now to see if memo exists at all
                
            \Log::info('ST Memo exists: ' . ($hasSTMemo ? 'YES' : 'NO'));
                
            if (!$hasSTMemo) {
                $validationErrors[] = 'ST Memo has not been generated for this application (Main App ID: ' . $memoApplicationId . ')';
            }
            
            // Check if RofO has been generated (RofO is linked to the sub application)
            \Log::info('Looking for RofO with sub_application_id: ' . $application->id);
            
            $hasRofo = DB::connection('sqlsrv')->table('rofo')
                ->where('sub_application_id', $application->id)
                ->exists(); // Remove the active check for now to see if rofo exists at all
                
            \Log::info('RofO exists: ' . ($hasRofo ? 'YES' : 'NO'));
                
            if (!$hasRofo) {
                $validationErrors[] = 'RofO (Right of Occupancy) has not been generated for this application (Sub App ID: ' . $application->id . ')';
            }
            
            // If there are validation errors, redirect back with error messages
            if (!empty($validationErrors)) {
                $errorMessage = 'Cannot generate Certificate of Occupancy. The following prerequisites are missing:';
                foreach ($validationErrors as $error) {
                    $errorMessage .= "\n• " . $error;
                }
                $errorMessage .= "\n\nPlease ensure all prerequisites are completed before generating the CofO.";
                
                \Log::error('Validation failed: ' . $errorMessage);
                return back()->with('error', $errorMessage);
            }
            
            \Log::info('All prerequisites met, proceeding with CofO generation');

            // Process owner names
            if (!empty($application->multiple_owners_names)) {
                $ownerArray = json_decode($application->multiple_owners_names, true);
                $application->owner_name = $ownerArray ? implode(', ', $ownerArray) : null;
            } elseif (!empty($application->corporate_name)) {
                $application->owner_name = $application->corporate_name;
            } else {
                $application->owner_name = trim($application->applicant_title . ' ' . $application->first_name . ' ' . $application->surname);
            }

            // Check if a CofO already exists for this application
            $existingCofO = DB::connection('sqlsrv')->table('st_cofo')
                ->where('sub_application_id', $id)
                ->where('is_active', 1)
                ->first();

            $certificateNumber = null;
            $nextAvailableCertificateNumber = null;
            
            if ($existingCofO) {
                $certificateNumber = $existingCofO->certificate_number;
            } else {
                // Generate the next available certificate number
                $nextAvailableCertificateNumber = $this->generateNextCertificateNumber();
            }

            // Calculate default values for certificate
            $startDate = now()->format('Y-m-d');
            $totalYears = 40; // Default value
            
            return view('programmes.generate_cofo', compact(
                'application',
                'startDate',
                'totalYears',
                'PageTitle',
                'PageDescription',
                'certificateNumber',
                'nextAvailableCertificateNumber'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Error generating CofO form: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while generating the Certificate of Occupancy form');
        }
    }
    
    // Save CofO to database
    public function saveCofO(Request $request)
    {
        $request->validate([
            'file_no' => 'required|string|max:50',
            'sub_application_id' => 'required|integer',
            'mother_application_id' => 'required|integer',
            'holder_name' => 'required|string|max:255',
            'holder_address' => 'required|string',
            'land_use' => 'required|string|max:100',
            'start_date' => 'required|date',
            'total_term' => 'required|integer|min:1|max:99',
            'signed_by' => 'required|string|max:255',
            'signed_title' => 'required|string|max:255',
        ]);
        
        try {
            // Calculate remaining term
            $startDate = \Carbon\Carbon::parse($request->start_date);
            $currentYear = now()->year;
            $elapsedYears = $currentYear - $startDate->year;
            $remainingTerm = max(0, $request->total_term - $elapsedYears);
            
            // Generate a unique certificate number (format: ST/COFO/YEAR/XXXX)
            $year = date('Y');
            $lastCofO = DB::connection('sqlsrv')->table('st_cofo')
                ->where('certificate_number', 'like', "ST/COFO/$year/%")
                ->orderBy('id', 'desc')
                ->first();
                
            $lastNumber = 0;
            if ($lastCofO) {
                $parts = explode('/', $lastCofO->certificate_number);
                $lastNumber = (int)end($parts);
            }
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            $certificateNumber = "ST/COFO/$year/$newNumber";
            
            // Insert record into st_cofo table
            $id = DB::connection('sqlsrv')->table('st_cofo')->insertGetId([
                'file_no' => $request->file_no,
                'sub_application_id' => $request->sub_application_id,
                'mother_application_id' => $request->mother_application_id,
                'plot_no' => $request->plot_no,
                'block_no' => $request->block_no,
                'floor_no' => $request->floor_no,
                'flat_no' => $request->flat_no,
                'holder_name' => $request->holder_name,
                'holder_address' => $request->holder_address,
                'certificate_number' => $certificateNumber,
                'land_use' => $request->land_use,
                'start_date' => $request->start_date,
                'total_term' => $request->total_term,
                'remaining_term' => $remainingTerm,
                'property_house_no' => $request->property_house_no,
                'property_street_name' => $request->property_street_name,
                'property_district' => $request->property_district,
                'property_lga' => $request->property_lga,
                'property_state' => $request->property_state ?? 'Kano',
                'issued_date' => now(),
                'signed_by' => $request->signed_by,
                'signed_title' => $request->signed_title,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => Auth::id(),
                'is_active' => 1
            ]);
            
            return redirect()->route('programmes.view_cofo', $request->sub_application_id)
                ->with('success', ' CofO Front Page Generated Successfully');
                
        } catch (\Exception $e) {
            \Log::error('Error saving CofO: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while saving the CofO Front Page: ' . $e->getMessage())
                ->withInput();
        }
    }

    // View CofO
    public function ViewCofO($id)
    {
        $PageTitle = 'Certificate of Occupancy';
        $PageDescription = 'Sectional Title Certificate';

        try {
            // Check if a CofO record exists for this application
            $cofo = DB::connection('sqlsrv')->table('st_cofo')
                ->where('sub_application_id', $id)
                ->where('is_active', 1)
                ->first();
                
            // If CofO not found, redirect to generate
            if (!$cofo) {
                // Get application data for redirect
                $application = DB::connection('sqlsrv')->table('subapplications')
                    ->where('id', $id)
                    ->first();
                    
                if (!$application) {
                    return back()->with('error', 'Application not found');
                }
                
                return redirect()->route('programmes.generate_cofo', $id)
                    ->with('info', 'CofO Front Page has not been generated yet. Please generate it.');
            }

            return view('programmes.view_cofo', compact(
                'cofo',
                'PageTitle',
                'PageDescription'
            ));
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error in ViewCofO: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while fetching certificate data');
        }
    }

    // Print CofO in new tab
    public function PrintCofO($id)
    {
        $PageTitle = 'Certificate of Occupancy - Print';
        $PageDescription = 'Sectional Title Certificate for Printing';

        try {
            // Check if a CofO record exists for this application
            $cofo = DB::connection('sqlsrv')->table('st_cofo')
                ->where('sub_application_id', $id)
                ->where('is_active', 1)
                ->first();
                
            // If CofO not found, redirect to generate
            if (!$cofo) {
                return back()->with('error', 'Certificate of Occupancy not found. Please generate it first.');
            }

            return view('programmes.cofo_print', compact(
                'cofo',
                'PageTitle',
                'PageDescription'
            ));
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error in PrintCofO: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while preparing certificate for printing');
        }
    }
}