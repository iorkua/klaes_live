<?php

namespace App\Http\Controllers;

use App\Models\InstrumentType;
use Illuminate\Http\Request;

class InstrumentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $instrumentTypes = InstrumentType::active()->orderBy('name')->get();
        return response()->json($instrumentTypes);
    }

    /**
     * Get all instrument types for dropdown/select options
     */
    public function getAll()
    {
        $instrumentTypes = InstrumentType::active()
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();
        
        return response()->json($instrumentTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:instrument_types',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $instrumentType = InstrumentType::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Instrument type created successfully',
            'data' => $instrumentType
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(InstrumentType $instrumentType)
    {
        return response()->json($instrumentType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InstrumentType $instrumentType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:instrument_types,name,' . $instrumentType->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $instrumentType->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Instrument type updated successfully',
            'data' => $instrumentType
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InstrumentType $instrumentType)
    {
        $instrumentType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Instrument type deleted successfully'
        ]);
    }

    /**
     * Toggle the active status of an instrument type
     */
    public function toggleStatus(InstrumentType $instrumentType)
    {
        $instrumentType->update(['is_active' => !$instrumentType->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Instrument type status updated successfully',
            'data' => $instrumentType
        ]);
    }
}