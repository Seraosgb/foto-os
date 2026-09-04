<?php
use App\Http\Controllers\Api\PhotoController;

// Aqui futuramente envolveremos no middleware auth:sanctum
Route::prefix('v1')->group(function () {
    Route::post('/reports', [ReportController::class, 'store']);
    Route::post('/reports/{report}/photos', [PhotoController::class, 'store']);
    Route::post('/reports/{report}/finalize', FinalizeReportController::class); // <--- NOVA ROTA
});
