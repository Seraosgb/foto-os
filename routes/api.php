<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\FinalizeReportController;

Route::prefix('v1')->group(function () {
    Route::post('/reports', [ReportController::class, 'store']);
    Route::post('/reports/{report}/photos', [PhotoController::class, 'store']);

    // Controller invocável deve ser passado assim em formato de array:
    Route::post('/reports/{report}/finalize', [FinalizeReportController::class, '__invoke']);
    Route::patch('photos/{photo}', [App\Http\Controllers\Api\PhotoController::class, 'updateObservation']);
    Route::patch('reports/{report}/photos/reorder', [App\Http\Controllers\Api\PhotoController::class, 'reorder']);
    Route::get('/reports/search', [ReportController::class, 'searchByOs']);
    Route::post('/reports/{report}/reopen', [ReportController::class, 'reopen']);
});
