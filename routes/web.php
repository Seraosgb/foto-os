<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Models\Report;
use App\Services\ReportPdfService;

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
