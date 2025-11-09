<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;

class RDSController extends Controller
{
    /**
     * Generate RDS (Registered Document Sheet) for an instrument
     */
    public function generateRDS($id)
    {
        try {
            // Get the instrument details
            $instrument = DB::connection('sqlsrv')
                ->table('registered_instruments')
                ->where('id', $id)
                ->first();

            if (!$instrument) {
                return response()->json([
                    'success' => false,
                    'error' => 'Instrument not found'
                ], 404);
            }

            // Check if instrument is registered
            if ($instrument->status !== 'registered') {
                return response()->json([
                    'success' => false,
                    'error' => 'Only registered instruments can have RDS generated'
                ], 400);
            }

            // Check if RDS already exists
            $existingRDS = DB::connection('sqlsrv')
                ->table('rds_tracking')
                ->where('instrument_id', $id)
                ->first();

            if ($existingRDS) {
                return response()->json([
                    'success' => false,
                    'error' => 'RDS has already been generated for this instrument',
                    'rds_id' => $existingRDS->id,
                    'generated_at' => $existingRDS->generated_at
                ], 400);
            }

            // Generate RDS reference number
            $rdsReference = $this->generateRDSReference();

            // Get additional details for the RDS
            $instrumentDetails = $this->getInstrumentDetails($instrument);

            // Create RDS tracking record
            $rdsId = DB::connection('sqlsrv')
                ->table('rds_tracking')
                ->insertGetId([
                    'instrument_id' => $id,
                    'stm_ref' => $instrument->STM_Ref,
                    'rds_reference' => $rdsReference,
                    'instrument_type' => $instrument->instrument_type,
                    'grantor' => $instrument->Grantor,
                    'grantee' => $instrument->Grantee,
                    'file_number' => $instrument->fileno ?? '',
                    'registration_date' => $instrument->instrumentDate ?? null,
                    'generated_by' => Auth::id(),
                    'generated_at' => now(),
                    'status' => 'generated',
                    'print_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            // Log the generation
            Log::info("RDS generated", [
                'rds_id' => $rdsId,
                'instrument_id' => $id,
                'stm_ref' => $instrument->STM_Ref,
                'rds_reference' => $rdsReference,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'RDS generated successfully',
                'rds_id' => $rdsId,
                'rds_reference' => $rdsReference,
                'rds_url' => route('rds.view', ['id' => $id])
            ]);

        } catch (\Exception $e) {
            Log::error("Error generating RDS: " . $e->getMessage(), [
                'instrument_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while generating RDS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View/Print RDS for an instrument
     */
    public function viewRDS($id)
    {
        try {
            // Get the RDS tracking record
            $rds = DB::connection('sqlsrv')
                ->table('rds_tracking')
                ->where('instrument_id', $id)
                ->first();

            if (!$rds) {
                // Check if it's an AJAX request
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'RDS has not been generated yet for this instrument'
                    ], 404);
                }
                
                // Direct access - redirect with error
                return redirect()->route('instrument_registration.index')
                    ->with('error', 'RDS has not been generated yet for this instrument');
            }

            // Get the instrument details
            $instrument = DB::connection('sqlsrv')
                ->table('registered_instruments')
                ->where('id', $id)
                ->first();

            if (!$instrument) {
                // Check if it's an AJAX request
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Instrument not found'
                    ], 404);
                }
                
                // Direct access - redirect with error
                return redirect()->route('instrument_registration.index')
                    ->with('error', 'Instrument not found');
            }

            // Increment print count
            DB::connection('sqlsrv')
                ->table('rds_tracking')
                ->where('id', $rds->id)
                ->update([
                    'print_count' => DB::raw('print_count + 1'),
                    'last_printed_at' => now(),
                    'last_printed_by' => Auth::id(),
                    'updated_at' => now()
                ]);

            // Check if it's an AJAX request
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'rds_url' => route('rds.print', ['id' => $id])
                ]);
            }
            
            // Direct access - redirect to print template
            return redirect()->route('rds.print', ['id' => $id]);

        } catch (\Exception $e) {
            Log::error("Error viewing RDS: " . $e->getMessage(), [
                'instrument_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            // Check if it's an AJAX request
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'An error occurred while viewing RDS'
                ], 500);
            }
            
            // Direct access - redirect with error
            return redirect()->route('instrument_registration.index')
                ->with('error', 'An error occurred while viewing RDS');
        }
    }

    /**
     * Print/Display RDS HTML template
     */
    public function printRDS($id)
    {
        try {
            // Get the RDS tracking record
            $rds = DB::connection('sqlsrv')
                ->table('rds_tracking')
                ->where('instrument_id', $id)
                ->first();

            if (!$rds) {
                abort(404, 'RDS not found');
            }

            // Get the instrument details
            $instrument = DB::connection('sqlsrv')
                ->table('registered_instruments')
                ->where('id', $id)
                ->first();

            if (!$instrument) {
                abort(404, 'Instrument not found');
            }

            // Get additional details
            $details = $this->getInstrumentDetails($instrument);

            // Prepare data for the view
            $data = [
                'rds' => $rds,
                'instrument' => $instrument,
                'details' => $details,
                'printCount' => $rds->print_count,
                'watermark' => $rds->print_count > 1 ? 'COPY' : 'ORIGINAL'
            ];

            return view('instrument_registration.rds.print', $data);

        } catch (\Exception $e) {
            Log::error("Error printing RDS: " . $e->getMessage(), [
                'instrument_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'An error occurred while loading RDS');
        }
    }

    /**
     * Get RDS status for an instrument
     */
    public function getRDSStatus($id)
    {
        try {
            $rds = DB::connection('sqlsrv')
                ->table('rds_tracking')
                ->where('instrument_id', $id)
                ->first();

            if (!$rds) {
                return response()->json([
                    'success' => true,
                    'exists' => false,
                    'message' => 'RDS not generated'
                ]);
            }

            return response()->json([
                'success' => true,
                'exists' => true,
                'rds' => [
                    'id' => $rds->id,
                    'rds_reference' => $rds->rds_reference,
                    'generated_at' => $rds->generated_at,
                    'generated_by' => $rds->generated_by,
                    'print_count' => $rds->print_count,
                    'last_printed_at' => $rds->last_printed_at,
                    'status' => $rds->status
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Error getting RDS status: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while checking RDS status'
            ], 500);
        }
    }

    /**
     * Generate unique RDS reference number
     */
    private function generateRDSReference()
    {
        $year = date('Y');
        $prefix = "RDS-{$year}-";

        // Get the latest RDS reference for the current year
        $latestRef = DB::connection('sqlsrv')
            ->table('rds_tracking')
            ->where('rds_reference', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->value('rds_reference');

        if ($latestRef) {
            // Extract the sequence number
            $matches = [];
            if (preg_match('/RDS-\d{4}-(\d{5})/', $latestRef, $matches)) {
                $sequence = (int)$matches[1] + 1;
            } else {
                $sequence = 1;
            }
        } else {
            $sequence = 1;
        }

        return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get detailed information about the instrument
     */
    private function getInstrumentDetails($instrument)
    {
        $details = [
            'registration_date' => $instrument->instrumentDate ? Carbon::parse($instrument->instrumentDate)->format('d/m/Y') : '',
            'registration_time' => $instrument->instrumentDate ? Carbon::parse($instrument->instrumentDate)->format('H:i:s') : '',
            'instrument_date' => $instrument->deeds_date ?? '',
            'instrument_time' => $instrument->deeds_time ?? '',
            'consideration' => $instrument->consideration ?? 'N/A',
            'stamp_duty' => $instrument->stamp_duty ?? 'N/A',
            'registration_fee' => $instrument->registration_fee ?? 'N/A',
            'plot_number' => $instrument->plotNumber ?? '',
            'plot_size' => $instrument->size ?? '',
            'plot_description' => $instrument->propertyDescription ?? '',
            'lga' => $instrument->lga ?? '',
            'district' => $instrument->district ?? '',
            'duration' => $instrument->duration ?? $instrument->leasePeriod ?? '',
            'root_registration_number' => $instrument->rootRegistrationNumber ?? $instrument->Deeds_Serial_No ?? '',
            'solicitor_name' => $instrument->solicitorName ?? '',
            'solicitor_address' => $instrument->solicitorAddress ?? '',
            'land_use_type' => $instrument->landUseType ?? $instrument->land_use ?? ''
        ];

        return $details;
    }

    /**
     * Delete/Cancel RDS (admin only)
     */
    public function deleteRDS($id)
    {
        try {
            // Check admin permission
            if (!Auth::user() || Auth::user()->type !== 'super admin') {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized. Only administrators can delete RDS records.'
                ], 403);
            }

            $rds = DB::connection('sqlsrv')
                ->table('rds_tracking')
                ->where('instrument_id', $id)
                ->first();

            if (!$rds) {
                return response()->json([
                    'success' => false,
                    'error' => 'RDS not found'
                ], 404);
            }

            // Mark as cancelled instead of deleting
            DB::connection('sqlsrv')
                ->table('rds_tracking')
                ->where('id', $rds->id)
                ->update([
                    'status' => 'cancelled',
                    'cancelled_by' => Auth::id(),
                    'cancelled_at' => now(),
                    'updated_at' => now()
                ]);

            Log::info("RDS cancelled", [
                'rds_id' => $rds->id,
                'instrument_id' => $id,
                'cancelled_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'RDS has been cancelled successfully'
            ]);

        } catch (\Exception $e) {
            Log::error("Error deleting RDS: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while deleting RDS'
            ], 500);
        }
    }

    /**
     * List all RDS records with filters
     */
    public function listRDS(Request $request)
    {
        try {
            $query = DB::connection('sqlsrv')
                ->table('rds_tracking as rds')
                ->leftJoin('registered_instruments as ri', 'rds.instrument_id', '=', 'ri.id')
                ->select(
                    'rds.*',
                    'ri.status as instrument_status',
                    'ri.instrument_type',
                    'ri.fileno'
                );

            // Apply filters
            if ($request->has('status')) {
                $query->where('rds.status', $request->status);
            }

            if ($request->has('instrument_type')) {
                $query->where('ri.instrument_type', $request->instrument_type);
            }

            if ($request->has('date_from')) {
                $query->whereDate('rds.generated_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('rds.generated_at', '<=', $request->date_to);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('rds.rds_reference', 'like', "%{$search}%")
                      ->orWhere('rds.stm_ref', 'like', "%{$search}%")
                      ->orWhere('ri.fileno', 'like', "%{$search}%")
                      ->orWhere('rds.grantor', 'like', "%{$search}%")
                      ->orWhere('rds.grantee', 'like', "%{$search}%");
                });
            }

            $records = $query->orderBy('rds.generated_at', 'desc')
                ->paginate($request->get('per_page', 50));

            return response()->json([
                'success' => true,
                'data' => $records
            ]);

        } catch (\Exception $e) {
            Log::error("Error listing RDS: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching RDS records'
            ], 500);
        }
    }
}
