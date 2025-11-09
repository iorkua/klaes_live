<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinalConveyanceController;
use App\Http\Controllers\CommissionNewSTController;
use App\Http\Controllers\GroupingDashboardController;
use App\Http\Controllers\ScanUploadsController;
use App\Http\Controllers\STFileNumberController;

/*
|--------------------------------------------------------------------------
| App3 Routes
|--------------------------------------------------------------------------
|
| Additional application routes for Final Conveyance and other features
|
*/

Route::middleware(['auth'])->group(function () {
    
    // Final Conveyance Routes
    Route::prefix('final-conveyance')->name('final-conveyance.')->group(function () {
        Route::get('/info/{id}', [FinalConveyanceController::class, 'show'])->name('info');
        Route::post('/generate', [FinalConveyanceController::class, 'generate'])->name('generate');
        
        // Buyer management routes
        Route::get('/buyers/{applicationId}', [FinalConveyanceController::class, 'getBuyers'])->name('buyers.list');
        Route::put('/buyers/{id}', [FinalConveyanceController::class, 'updateBuyer'])->name('buyers.update');
        Route::delete('/buyers/{id}', [FinalConveyanceController::class, 'deleteBuyer'])->name('buyers.delete');
    });

    // Grouping Analytics Dashboard
    Route::prefix('grouping-analytics')->name('grouping-analytics.')->group(function () {
        Route::get('/', [GroupingDashboardController::class, 'index'])->name('dashboard');
        Route::get('/data', [GroupingDashboardController::class, 'data'])->name('data');
    });

    // Scan Uploads routes (full CRUD + logging + debug)
    Route::prefix('scan-uploads')->name('scan-uploads.')->group(function () {
        Route::get('/', [ScanUploadsController::class, 'index'])->name('index');
        Route::get('/log', [ScanUploadsController::class, 'log'])->name('log');
        Route::post('/upload', [ScanUploadsController::class, 'upload'])->name('upload');
        Route::delete('/{scan}', [ScanUploadsController::class, 'destroy'])->name('destroy');
        Route::get('/debug', [ScanUploadsController::class, 'debug'])->name('debug');
    });

    // Commission New ST Routes
    Route::prefix('commission-new-st')->name('commission-new-st.')->group(function () {
        Route::get('/', [CommissionNewSTController::class, 'index'])->name('index');
        Route::get('/primary-data', [CommissionNewSTController::class, 'getPrimaryData'])->name('primary.data');
        Route::get('/sua-data', [CommissionNewSTController::class, 'getSuAData'])->name('sua.data');
        Route::get('/pua-data', [CommissionNewSTController::class, 'getPuAData'])->name('pua.data');
        
        // File number generation endpoints
        Route::get('/next-fileno', [CommissionNewSTController::class, 'nextFileNo'])->name('next-fileno');
        Route::get('/sua-next-fileno', [CommissionNewSTController::class, 'suaNextFileNo'])->name('sua.next-fileno');
        Route::get('/pua-next-fileno', [CommissionNewSTController::class, 'puaNextFileNo'])->name('pua.next-fileno');
        Route::post('/commission', [CommissionNewSTController::class, 'commission'])->name('commission');
        Route::post('/commission-sua', [CommissionNewSTController::class, 'commissionSuA'])->name('commission-sua');
        Route::post('/commission-pua', [CommissionNewSTController::class, 'commissionPuA'])->name('commission-pua');
    });

    // ST File Number API Routes
    Route::prefix('api/st-file-numbers')->name('api.st-file-numbers.')->group(function () {
        // File number generation endpoints
        Route::post('/reserve-primary', [STFileNumberController::class, 'reservePrimary'])->name('reserve-primary');
        Route::post('/reserve-sua', [STFileNumberController::class, 'reserveSUA'])->name('reserve-sua');
        Route::post('/reserve-pua', [STFileNumberController::class, 'reservePUA'])->name('reserve-pua');
        
        // Commission New ST endpoints
        Route::get('/primary-available', [CommissionNewSTController::class, 'getAvailablePrimaryFileNumbers'])->name('primary-available');
        
        // File number management endpoints
        Route::post('/confirm/{fileNumber}', [STFileNumberController::class, 'confirm'])->name('confirm');
        Route::delete('/release/{fileNumber}', [STFileNumberController::class, 'release'])->name('release');
        Route::get('/details/{fileNumber}', [STFileNumberController::class, 'getDetails'])->name('details');
        Route::get('/units/{parentFileNumber}', [STFileNumberController::class, 'getUnitsByParent'])->name('units');
        Route::get('/buyers/{parentFileNumber}', [STFileNumberController::class, 'getBuyersForParent'])->name('buyers');
        
        // Preview endpoints (for UI display without reserving)
        Route::post('/preview', [STFileNumberController::class, 'getNextPreview'])->name('preview');
        
        // Validation and search endpoints
        Route::get('/validate/{fileNumber}', [STFileNumberController::class, 'validateFileNumber'])->name('validate');
        Route::get('/search', [STFileNumberController::class, 'search'])->name('search');
    });
    
    // Test route for ST File Number Service
    Route::get('/test-st-file-numbers', function () {
        return view('test-st-file-numbers');
    });

    Route::get('/printlabel/print-file-lab', function () {
        return view('printlabel.print-file-lab');
    })->name('printlabel.print-template');
    
});

 