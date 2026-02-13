<?php

use App\Http\Controllers\DashboardOperationsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard/quotations');
});

Route::redirect('/dashboard', '/dashboard/quotations');
Route::view('/dashboard/quotations', 'quotations');
Route::get('/dashboard/operations', [DashboardOperationsController::class, 'index']);
Route::get('/dashboard/operations/auto-collect', [DashboardOperationsController::class, 'showAutoCollectConfig']);
Route::get('/dashboard/operations/auto-collect/history', [DashboardOperationsController::class, 'listAutoCollectHistory']);
Route::get('/dashboard/operations/auto-collect/status', [DashboardOperationsController::class, 'showAutoCollectStatus']);
Route::put('/dashboard/operations/auto-collect', [DashboardOperationsController::class, 'updateAutoCollectConfig']);
Route::post('/dashboard/operations/auto-collect/run', [DashboardOperationsController::class, 'runAutoCollect']);
Route::post('/dashboard/operations/auto-collect/health/reset', [DashboardOperationsController::class, 'resetAutoCollectHealth']);
Route::post('/dashboard/operations/auto-collect/cancel', [DashboardOperationsController::class, 'cancelAutoCollect']);

require __DIR__.'/auth.php';
