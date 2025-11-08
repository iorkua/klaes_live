<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FileIndexingController;
use App\Http\Controllers\InstrumentController;
use App\Http\Controllers\InstrumentTypeController;
use App\Http\Controllers\FileNumberApiController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\Api\GroupingController as GroupingAnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for application data
Route::get('/get-application-data', function (Request $request) {
    $applicationId = $request->input('application_id');
    
    if (!$applicationId) {
        return response()->json(['error' => 'No application ID provided'], 400);
    }
    
    try {
        $applicationData = DB::connection('sqlsrv')
            ->table('dbo.mother_applications')
            ->where('id', $applicationId)
            ->first();
            
        if ($applicationData) {
            return response()->json([
                'success' => true,
                'data' => $applicationData
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No data found for this application ID'
            ], 404);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving application data',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('api.get-application-data');

// File Indexing API Endpoints
Route::get('/file-records', [FileIndexingController::class, 'getAllRecords']);
Route::get('/file-records/{id}', [FileIndexingController::class, 'getRecord']);
Route::post('/file-records/search', [FileIndexingController::class, 'searchRecords']);
Route::get('/file-indexings/lookup-by-number', [FileIndexingController::class, 'lookupByFileNumber']);

// New API endpoints for CofO and Property Transaction data
Route::get('/cofo-record/{fileNo}', [FileIndexingController::class, 'getCofORecord']);
Route::get('/property-transaction', [FileIndexingController::class, 'getPropertyTransactionRecord']);

// Instruments API Routes - Not requiring authentication for now to fix the immediate issue
Route::post('/instruments/generate-particulars', [InstrumentController::class, 'generateParticulars']);

// Instrument Types API Routes
Route::get('/instrument-types', [InstrumentTypeController::class, 'getAll']);

// Route for fetching sub-final-bill details
Route::get('/sub-final-bill/show/{id}', [App\Http\Controllers\SubFinalBillController::class, 'show']);

// Route for fetching application details
Route::get('/application-details/{fileId}/{fileType}', [App\Http\Controllers\ProgrammesController::class, 'getApplicationDetails']);

// Route for fetching shared utilities data
Route::get('/shared-utilities/{application_id}/{sub_application_id?}', function (Request $request, $application_id, $sub_application_id = null) {
    try {
        $query = DB::connection('sqlsrv')
            ->table('shared_utilities')
            ->where('application_id', $application_id)
            ->select([
                'id',
                'application_id', 
                'sub_application_id',
                'utility_type',
                'dimension',
                'count',
                'order',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at'
            ]);
        
        // If sub_application_id is provided, filter by it
        if ($sub_application_id) {
            $query->where('sub_application_id', $sub_application_id);
        }
        
        $sharedUtilities = $query->orderBy('order')->get();
        
        return response()->json([
            'success' => true,
            'data' => $sharedUtilities,
            'count' => $sharedUtilities->count(),
            'application_id' => $application_id,
            'sub_application_id' => $sub_application_id
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving shared utilities data',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('api.shared-utilities');

// Route for fetching existing MLS file numbers for extensions
Route::get('/get-existing-mls-files', function (Request $request) {
    try {
        // Query the fileNumber table to get existing MLS file numbers
        $mlsFiles = DB::connection('sqlsrv')
            ->table('dbo.fileNumber')
            ->whereNotNull('mlsfNo')
            ->where('mlsfNo', '!=', '')
            ->select('id', 'mlsfNo')
            ->orderBy('id', 'desc')
            ->limit(500) // Limit to prevent overwhelming the dropdown
            ->get();

        if ($mlsFiles->count() > 0) {
            return response()->json([
                'success' => true,
                'files' => $mlsFiles->map(function($file) {
                    return [
                        'id' => $file->id,
                        'mlsFNo' => $file->mlsfNo,
                        'file_number' => $file->mlsfNo
                    ];
                })
            ]);
        } else {
            return response()->json([
                'success' => true,
                'files' => [],
                'message' => 'No existing MLS file numbers found'
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error retrieving MLS file numbers',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('api.get-existing-mls-files');

// Route for searching file numbers for property records
Route::post('/search-file-numbers', [App\Http\Controllers\PropertyRecordController::class, 'searchFileNumbers']);

// Test route to debug file number search
Route::get('/test-file-numbers', function() {
    return response()->json([
        'success' => true,
        'message' => 'API endpoint is working',
        'sample_data' => [
            [
                'id' => 'test_1',
                'fileno' => 'TEST-001',
                'description' => 'Test File Number 1',
                'plot_no' => '123',
                'lga' => 'Test LGA',
                'location' => 'Test Location',
                'source' => 'test'
            ],
            [
                'id' => 'test_2', 
                'fileno' => 'TEST-002',
                'description' => 'Test File Number 2',
                'plot_no' => '456',
                'lga' => 'Test LGA 2',
                'location' => 'Test Location 2',
                'source' => 'test'
            ]
        ]
    ]);
});

// File Tracking API Routes - KLAES Lands Module File Tracker
use App\Http\Controllers\FileTrackingController;

// Main file tracking endpoints
Route::get('/file-trackings', [FileTrackingController::class, 'index']);
Route::post('/file-trackings', [FileTrackingController::class, 'store']);
Route::get('/file-trackings/{id}', [FileTrackingController::class, 'show']);
Route::put('/file-trackings/{id}', [FileTrackingController::class, 'update']);
Route::delete('/file-trackings/{id}', [FileTrackingController::class, 'destroy']);

// Movement tracking
Route::post('/file-trackings/{id}/move', [FileTrackingController::class, 'addMovement']);

// RFID integration endpoints
Route::post('/rfid/register', [FileTrackingController::class, 'registerRfid']);
Route::get('/rfid/scan/{tag}', [FileTrackingController::class, 'scanRfid']);
Route::get('/rfid/report', [FileTrackingController::class, 'generateReport']);

// Batch operations
Route::post('/file-trackings/batch/overdue', [FileTrackingController::class, 'batchUpdateOverdue']);
Route::get('/fileindexing/batch/{batch_id}/file-ids', [\App\Http\Controllers\FileIndexController::class, 'getBatchFileIds']);

// File Number API Routes for Global Modal
Route::get('/test-api', function() {
    return response()->json(['success' => true, 'message' => 'API is working']);
});

// Centralised File Number API (controller-based)
Route::prefix('file-numbers')->controller(FileNumberApiController::class)->group(function () {
    Route::get('/', 'index')->name('api.file-numbers.index');
    Route::post('/', 'store')->name('api.file-numbers.store');
    Route::get('/lookup', 'lookup')->name('api.file-numbers.lookup');
    Route::get('/tracking/{trackingId}', 'showByTracking')->name('api.file-numbers.show-tracking');

    // Legacy file number systems
    Route::get('/mls', 'mls');
    Route::get('/kangis', 'kangis');
    Route::get('/newkangis', 'newKangis');
    
    // New ST file number system
    Route::get('/st-all', 'getAllSTFileNumbers');
    Route::get('/st-stats', 'getSTFileNumberStats');
    Route::get('/st-dropdown-data', 'getSTDropdownData');
});

Route::prefix('reference')->controller(ReferenceDataController::class)->group(function () {
    Route::get('/lgas', 'lgas')->name('api.reference.lgas');
    Route::get('/districts', 'districts')->name('api.reference.districts');
});

// Serial Status API Route
Route::get('/serial-status', function () {
    try {
        $serials = DB::connection('sqlsrv')
            ->table('land_use_serials')
            ->where('year', date('Y'))
            ->orderBy('land_use_type')
            ->get();
        
        return response()->json([
            'success' => true,
            'serials' => $serials
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
});

// Dashboard Statistics API Routes
Route::prefix('dashboard')->controller(\App\Http\Controllers\Api\DashboardController::class)->group(function () {
    Route::get('/total-applications', 'getTotalApplications');
    Route::get('/pending-approvals', 'getPendingApprovals');
    Route::get('/indexed-files', 'getIndexedFiles');
    Route::get('/blind-scans', 'getBlindScans');
    Route::get('/scan-uploads', 'getScanUploads');
});

// Grouping Analytics Fast API Routes (sample-based stats)
Route::prefix('grouping-analytics')->controller(GroupingAnalyticsController::class)->group(function () {
    Route::get('/stats', 'stats')->name('api.grouping-analytics.stats');
    Route::get('/preview', 'preview')->name('api.grouping-analytics.preview');
    Route::get('/search', 'search')->name('api.grouping-analytics.search');
    Route::get('/debug', 'debug')->name('api.grouping-analytics.debug');
    Route::delete('/cache', 'clearCache')->name('api.grouping-analytics.clear-cache');
});

// Grouping API Routes - Comprehensive Global API with PERFORMANCE OPTIMIZATIONS
Route::prefix('grouping')->controller(\App\Http\Controllers\Api\GroupingApiController::class)->group(function () {
    // Statistics and totals
    Route::get('/totals', 'totals')->name('api.grouping.totals');
    Route::get('/fast-stats', 'fastStats')->name('api.grouping.fast-stats'); // NEW: Optimized stats
    Route::get('/land-use-types', 'landUseTypes')->name('api.grouping.land-use-types');
    Route::get('/available-years', 'availableYears')->name('api.grouping.available-years');
    
    // Search and filtering
    Route::get('/search', 'search')->name('api.grouping.search');
    Route::get('/land-use/{landuse}', 'byLandUse')->name('api.grouping.by-land-use');
    Route::get('/awaiting/{fileno}', 'findByAwaitingFileno')->name('api.grouping.awaiting');
    Route::post('/bulk-lookup', 'bulkLookup')->name('api.grouping.bulk-lookup'); // NEW: Bulk operations
    
    // Standard REST operations
    Route::get('/', 'index')->name('api.grouping.index');
    Route::post('/', 'store')->name('api.grouping.store');
    Route::get('/{id}', 'show')->name('api.grouping.show');
    Route::put('/{id}', 'update')->name('api.grouping.update');
    Route::delete('/{id}', 'destroy')->name('api.grouping.destroy');
});