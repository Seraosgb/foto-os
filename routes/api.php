<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\FinalizeReportController;
use App\Http\Controllers\Api\TaxonomyController;

Route::prefix('v1')->group(function () {

    // 0. Taxonomia de Unidades e Setores
    Route::get('/taxonomies/units', [TaxonomyController::class, 'units']);

    // 1. Relatórios e Rascunhos
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/reports/search', [ReportController::class, 'searchByOs']);
    Route::post('/reports/{report}/reopen', [ReportController::class, 'reopen']);
    Route::post('/reports/{report}/finalize', [FinalizeReportController::class, '__invoke']);

    // 2. Fotos e Evidências Fotográficas
    Route::post('/reports/{report}/photos', [PhotoController::class, 'store']);
    Route::patch('/reports/{report}/photos/reorder', [PhotoController::class, 'reorder']);
    Route::patch('/photos/{photo}', [PhotoController::class, 'updateObservation']);
});
