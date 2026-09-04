<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\FinalizeReportController; // <--- GARANTA QUE ESTÁ AQUI

Route::prefix('v1')->group(function () {
    Route::post('/reports', [ReportController::class, 'store']);
    Route::post('/reports/{report}/photos', [PhotoController::class, 'store']);

    // Controller invocável deve ser passado assim em formato de array:
    Route::post('/reports/{report}/finalize', [FinalizeReportController::class, '__invoke']);
});
