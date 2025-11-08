<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Lga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceDataController extends Controller
{
    public function lgas(): JsonResponse
    {
        $lgas = Lga::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'slug']);

        return response()->json([
            'success' => true,
            'data' => $lgas,
        ]);
    }

    public function districts(Request $request): JsonResponse
    {
        $query = District::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->string('search'));
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $districts = $query->get(['id', 'name', 'slug']);

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }
}
