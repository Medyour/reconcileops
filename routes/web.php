<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReconciliationRunController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/runs', [ReconciliationRunController::class, 'index'])->name('runs.index');
Route::get('/runs/{reconciliationRun:public_id}', [ReconciliationRunController::class, 'show'])
    ->name('runs.show');
