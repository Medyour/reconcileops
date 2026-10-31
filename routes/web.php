<?php

use App\Http\Controllers\ArchitectureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReconciliationDecisionController;
use App\Http\Controllers\ReconciliationExceptionController;
use App\Http\Controllers\ReconciliationMatchController;
use App\Http\Controllers\ReconciliationRunController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/runs', [ReconciliationRunController::class, 'index'])->name('runs.index');
Route::get('/runs/{reconciliationRun:public_id}', [ReconciliationRunController::class, 'show'])
    ->name('runs.show');
Route::get('/matches', [ReconciliationMatchController::class, 'index'])->name('matches.index');
Route::get('/exceptions', [ReconciliationExceptionController::class, 'index'])->name('exceptions.index');
Route::post('/exceptions/{reconciliationException}/decision', [ReconciliationDecisionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('exceptions.decisions.store');
Route::get('/architecture', ArchitectureController::class)->name('architecture');
