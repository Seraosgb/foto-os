<?php
use App\Http\Controllers\Api\PhotoController;

// Aqui futuramente envolveremos no middleware auth:sanctum
Route::prefix('v1')->group(function () {
    // O {report} na URL espera o UUID da Ordem de Serviço
    Route::post('/reports/{report}/photos', [PhotoController::class, 'store']);
});
