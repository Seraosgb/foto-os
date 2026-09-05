<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pwa.index');
});
Route::middleware(['auth'])->prefix('painel')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
});
