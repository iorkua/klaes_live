<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CommissioningSheetController extends Controller
{
    /**
     * Store a new commissioning sheet
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_number' => 'required|string|max:255',
                'file_name' => 'nullable|string|max:500',
                'name_or_allottee' => 'nullable|string|max:500',
                'plot_number' => 'nullable|string|max:255',
                'tp_number' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:500',
                'date_created' => 'nullable|date',
                'created_by' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['created_user_id'] = Auth::id();
            $data['status'] = 'Draft';
            $data['created_at'] = now();
            $data['updated_at'] = now();

            // Insert into database
            $id = DB::connection('sqlsrv')
                ->table('file_commissioning_sheets')
                ->insertGetId($data);

            return response()->json([
                'success' => true,
                'message' => 'Commissioning sheet saved successfully',
                'data' => ['id' => $id]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving commissioning sheet: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save commissioning sheet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate and save commissioning sheet data (PDF generated on frontend)
     */
    public function generateAndPrint(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_number' => 'required|string|max:255',
                'file_name' => 'nullable|string|max:500',
                'name_or_allottee' => 'nullable|string|max:500',
                'plot_number' => 'nullable|string|max:255',
                'tp_number' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:500',
                'date_created' => 'nullable|date',
                'created_by' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['created_user_id'] = Auth::id();
            $data['status'] = 'Generated';
            $data['created_at'] = now();
            $data['updated_at'] = now();

            // Insert into database
            $id = DB::connection('sqlsrv')
                ->table('file_commissioning_sheets')
                ->insertGetId($data);

            return response()->json([
                'success' => true,
                'message' => 'Commissioning sheet data saved successfully',
                'data' => ['id' => $id]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving commissioning sheet data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save commissioning sheet data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all commissioning sheets
     */
    public function index()
    {
        try {
            $sheets = DB::connection('sqlsrv')
                ->table('file_commissioning_sheets')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sheets
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching commissioning sheets: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch commissioning sheets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific commissioning sheet
     */
    public function show($id)
    {
        try {
            $sheet = DB::connection('sqlsrv')
                ->table('file_commissioning_sheets')
                ->where('id', $id)
                ->first();

            if (!$sheet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commissioning sheet not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $sheet
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching commissioning sheet: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch commissioning sheet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a commissioning sheet
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_number' => 'required|string|max:255',
                'file_name' => 'nullable|string|max:500',
                'name_or_allottee' => 'nullable|string|max:500',
                'plot_number' => 'nullable|string|max:255',
                'tp_number' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:500',
                'date_created' => 'nullable|date',
                'created_by' => 'nullable|string|max:255',
                'approved_by' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:Draft,Approved,Printed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['updated_at'] = now();

            $affected = DB::connection('sqlsrv')
                ->table('file_commissioning_sheets')
                ->where('id', $id)
                ->update($data);

            if ($affected === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commissioning sheet not found or no changes made'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commissioning sheet updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating commissioning sheet: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update commissioning sheet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a commissioning sheet
     */
    public function destroy($id)
    {
        try {
            $affected = DB::connection('sqlsrv')
                ->table('file_commissioning_sheets')
                ->where('id', $id)
                ->delete();

            if ($affected === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commissioning sheet not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commissioning sheet deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting commissioning sheet: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete commissioning sheet: ' . $e->getMessage()
            ], 500);
        }
    }
}
