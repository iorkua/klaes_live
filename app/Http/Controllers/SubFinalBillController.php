<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use NumberFormatter;
use Carbon\Carbon;

class SubFinalBillController extends Controller
{
    /**
     * Generate a new final bill or display existing bill
     */
    public function generateBill($applicationId)
    {
        // Get application data
        $application = DB::connection('sqlsrv')
                         ->table('subapplications')
                         ->where('id', $applicationId)
                         ->first();
        
        if (!$application) {
            return redirect()->back()->with('error', 'Application not found');
        }
        
        // Get existing bill if any
        $existingBill = DB::connection('sqlsrv')
                           ->table('final_bills')
                           ->where('sub_application_id', $applicationId)
                           ->first();
        
        // Default fee values based on land use type
        $fees = $this->getDefaultFees($application->land_use);
        
        // If bill exists, use stored values
        if ($existingBill) {
            $fees = [
                'processing_fee' => 0, // Set to 0 since we're not using it
                'survey_fee' => $existingBill->survey_fee ?? 0,
                'assignment_fee' => $existingBill->assignment_fee,
                'bill_balance' => $existingBill->bill_balance,
                'recertification_fee' => $existingBill->recertification_fee,
                'dev_charges' => $existingBill->dev_charges,
                'total_amount' => $existingBill->total_amount
            ];
        }
        
        // Convert total to words
        $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
        $totalInWords = ucfirst($formatter->format($fees['total_amount'])) . ' Naira Only';
        
        return view('sub_actions.final_bill', [
            'application' => $application,
            'bill' => $existingBill,
            'fees' => $fees,
            'total_in_words' => $totalInWords,
            'current_date' => Carbon::now()->format('l, F d, Y')
        ]);
    }
    
    /**
     * Save final bill data
     */
    public function saveBill(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'sub_application_id' => 'required|integer',
                'assignment_fee' => 'required|numeric',
                'bill_balance' => 'required|numeric',
                'recertification_fee' => 'required|numeric',
                'dev_charges' => 'required|numeric', // Added development charges
                'bill_date' => 'required|date',
                'bill_status' => 'nullable|string|in:generated,sent,paid,cancelled'
            ]);
            
            // Set processing_fee and survey_fee to 0 since we're not using them in the form
            $validatedData['processing_fee'] = 0;
            $validatedData['survey_fee'] = 0;
            
            // Calculate total amount (without processing_fee)
            $validatedData['total_amount'] = 
                $validatedData['assignment_fee'] + 
                $validatedData['bill_balance'] + 
                $validatedData['recertification_fee'] +
                $validatedData['dev_charges'];
            
            // Set default status if not provided
            if (!isset($validatedData['bill_status'])) {
                $validatedData['bill_status'] = 'generated';
            }
            
            // Check if bill already exists - handle both integer and string IDs
            $subAppId = $validatedData['sub_application_id'];
            // Remove any filtering on amounts so we always find the bill for update
            $existingBill = DB::connection('sqlsrv')
                              ->table('final_bills')
                              ->where('sub_application_id', $subAppId)
                              ->first();
                              
            \Log::info('Checking for existing final bill:', [
                'sub_application_id' => $subAppId,
                'existing_bill_found' => $existingBill ? 'Yes (ID: ' . $existingBill->id . ')' : 'No'
            ]);
            
            if ($existingBill) {
                // Update existing bill
                DB::connection('sqlsrv')
                    ->table('final_bills')
                    ->where('sub_application_id', $validatedData['sub_application_id'])
                    ->update([
                        'processing_fee' => $validatedData['processing_fee'],
                        'survey_fee' => $validatedData['survey_fee'],
                        'assignment_fee' => $validatedData['assignment_fee'],
                        'bill_balance' => $validatedData['bill_balance'],
                        'recertification_fee' => $validatedData['recertification_fee'],
                        'dev_charges' => $validatedData['dev_charges'], // Added
                        'total_amount' => $validatedData['total_amount'],
                        'bill_date' => $validatedData['bill_date'],
                        'bill_status' => $validatedData['bill_status'],
                        'updated_at' => now()
                    ]);
                
                $message = 'Bill updated successfully';
            } else {
                // Create new bill
                DB::connection('sqlsrv')
                    ->table('final_bills')
                    ->insert([
                        'sub_application_id' => $validatedData['sub_application_id'],
                        'processing_fee' => $validatedData['processing_fee'],
                        'survey_fee' => $validatedData['survey_fee'],
                        'assignment_fee' => $validatedData['assignment_fee'],
                        'bill_balance' => $validatedData['bill_balance'],
                        'recertification_fee' => $validatedData['recertification_fee'],
                        'dev_charges' => $validatedData['dev_charges'], // Added
                        'total_amount' => $validatedData['total_amount'],
                        'bill_date' => $validatedData['bill_date'],
                        'bill_status' => $validatedData['bill_status'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                
                $message = 'Bill generated successfully';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Get the default fees based on land use type
     */
    private function getDefaultFees($landUse)
    {
        $landUse = strtolower($landUse ?: 'residential');
        
        // Default fee structure (removed processing_fee from calculations)
        if ($landUse == 'residential') {
            $processingFee = 0; // Not used in form
            $surveyFee = 0; // Not used in form
            $assignmentFee = 50000;
            $billBalance = 30525;
            $groundRent = 5000;
            $devCharges = 0; // Development charges typically calculated separately
        } else if ($landUse == 'commercial') {
            $processingFee = 0; // Not used in form
            $surveyFee = 0; // Not used in form
            $assignmentFee = 100000;
            $billBalance = 50000;
            $groundRent = 10000;
            $devCharges = 0; // Development charges typically calculated separately
        } else {
            // Default/Industrial/Others
            $processingFee = 0; // Not used in form
            $surveyFee = 0; // Not used in form
            $assignmentFee = 75000;
            $billBalance = 40000;
            $groundRent = 8000;
            $devCharges = 0; // Development charges typically calculated separately
        }
        
        // Calculate total without processing_fee
        $totalAmount = $assignmentFee + $billBalance + $groundRent + $devCharges;
        
        return [
            'processing_fee' => $processingFee,
            'survey_fee' => $surveyFee,
            'assignment_fee' => $assignmentFee,
            'bill_balance' => $billBalance,
            'recertification_fee' => $groundRent,
            'dev_charges' => $devCharges,
            'total_amount' => $totalAmount
        ];
    }
    
    /**
     * Print a final bill
     */
    public function printBill($applicationId)
    {
        try {
            \Log::info('Print bill requested for application ID: ' . $applicationId);
            
            $application = DB::connection('sqlsrv')
                             ->table('subapplications')
                             ->where('id', $applicationId)
                             ->first();
            
            if (!$application) {
                \Log::error('Application not found for ID: ' . $applicationId);
                return redirect()->back()->with('error', 'Application not found');
            }
            
            \Log::info('Application found: ' . $application->fileno);
            
            $bill = DB::connection('sqlsrv')
                      ->table('final_bills')
                      ->where('sub_application_id', $applicationId)
                      ->first();
            
            \Log::info('Bill found: ' . ($bill ? 'Yes (ID: ' . $bill->id . ')' : 'No'));
            
            if (!$bill) {
                \Log::info('No bill found, using default fees for land use: ' . $application->land_use);
                $fees = $this->getDefaultFees($application->land_use);
                
                $bill = (object)[
                    'processing_fee' => $fees['processing_fee'],
                    'survey_fee' => $fees['survey_fee'],
                    'assignment_fee' => $fees['assignment_fee'],
                    'bill_balance' => $fees['bill_balance'],
                    'recertification_fee' => $fees['recertification_fee'],
                    'dev_charges' => $fees['dev_charges'],
                    'total_amount' => $fees['total_amount'],
                    'bill_date' => Carbon::now()->format('Y-m-d'),
                    'bill_status' => 'generated'
                ];
                
                \Log::info('Default bill created with total: ' . $bill->total_amount);
            } else {
                \Log::info('Existing bill data: ', [
                    'assignment_fee' => $bill->assignment_fee,
                    'bill_balance' => $bill->bill_balance,
                    'total_amount' => $bill->total_amount
                ]);
            }
            
            // Convert total to words
            $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
            $totalInWords = ucfirst($formatter->format($bill->total_amount)) . ' Naira Only';
            
            \Log::info('Rendering print view with total in words: ' . $totalInWords);
            
            return view('sub_actions.print_final_bill', [
                'application' => $application,
                'bill' => $bill,
                'total_in_words' => $totalInWords,
                'current_date' => Carbon::now()->format('l, F d, Y')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in printBill: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Error generating print view: ' . $e->getMessage());
        }
    }

    /**
     * Show existing bill for unit application
     */
    public function showBill($applicationId)
    {
        try {
            // Get application data
            $application = DB::connection('sqlsrv')
                             ->table('subapplications')
                             ->where('id', $applicationId)
                             ->first();
            
            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }
            
            // Get existing bill
            $bill = DB::connection('sqlsrv')
                      ->table('final_bills')
                      ->where('sub_application_id', $applicationId)
                      ->first();
            
            if (!$bill) {
                return response()->json([
                    'success' => false,
                    'message' => 'No bill found for this application. Please generate a bill first.'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'bill' => $bill,
                'application' => $application
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving bill: ' . $e->getMessage()
            ], 500);
        }
    }
}