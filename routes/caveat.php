<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaveatController;

// Caveat Routes
Route::group(['middleware' => ['auth', 'XSS'], 'prefix' => 'caveat'], function () {
    // Main caveat page
    Route::get('/', [CaveatController::class, 'index'])->name('caveat.index');

    // APIs
    Route::get('/api', [CaveatController::class, 'indexApi'])->name('caveat.api.index');
    Route::post('/api', [CaveatController::class, 'store'])->name('caveat.api.store');
    Route::get('/api/stats', [CaveatController::class, 'stats'])->name('caveat.api.stats');
    Route::get('/api/{id}', [CaveatController::class, 'show'])->name('caveat.api.show');
    Route::post('/api/{id}/lift', [CaveatController::class, 'lift'])->name('caveat.api.lift');
    
    // File number search and record creation APIs
    Route::post('/api/search-file-number', [CaveatController::class, 'searchFileNumber'])->name('caveat.api.search-file-number');
    Route::post('/api/create-property-record', [CaveatController::class, 'createPropertyRecord'])->name('caveat.api.create-property-record');
}); 