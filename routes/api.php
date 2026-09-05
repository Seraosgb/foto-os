<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\FinalizeReportController;

// PWA Operacional (Sem restrição de login para o técnico de campo)
Route::get('/', function () {
    return view('pwa.index');
});

// Autenticação Administrativa
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Painel de Gestão Blindado
Route::middleware('auth')->prefix('painel')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::post('/empresa', [DashboardController::class, 'updateCompany']);
    Route::post('/unidades/{unit}/toggle', [DashboardController::class, 'toggleUnit']);

    // Rota de emissão sob demanda de PDF no painel
    Route::get('/relatorios/{report}/pdf', function (Report $report, ReportPdfService $pdfService) {
        $path = $pdfService->generate($report);
        return response()->file(storage_path('app/public/' . $path));
    });
});

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
