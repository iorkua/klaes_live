<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MlsFileNoController;

// MLS FileNo Management Routes
Route::group(['middleware' => ['auth', 'XSS'], 'prefix' => 'mls-fileno'], function () {
    Route::get('/', [MlsFileNoController::class, 'index'])->name('mls-fileno.index');
    Route::get('/data', [MlsFileNoController::class, 'getData'])->name('mls-fileno.data');
    Route::get('/datatable', [MlsFileNoController::class, 'getData'])->name('mls-fileno.datatable');
    Route::get('/stats', [MlsFileNoController::class, 'getStats'])->name('mls-fileno.stats');
    Route::get('/sources', [MlsFileNoController::class, 'getSources'])->name('mls-fileno.sources');
    Route::get('/debug', [MlsFileNoController::class, 'debug'])->name('mls-fileno.debug');
    Route::get('/{id}', [MlsFileNoController::class, 'show'])->name('mls-fileno.show');
    Route::put('/{id}', [MlsFileNoController::class, 'update'])->name('mls-fileno.update');
});