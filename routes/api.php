<?php

use App\Http\Controllers\Api\AuthenticatedUserController;
use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\QuotationController;
use Illuminate\Support\Facades\Route;

Route::middleware('gateway.only')->group(function () {
    // Public endpoint to exchange credentials for a Sanctum token.
    Route::post('/auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:10,1');

    // User-scoped resources protected by bearer token authentication.
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::delete('/auth/token', [AuthTokenController::class, 'destroy']);
        Route::get('/user', [AuthenticatedUserController::class, 'show']);
    });

    // Quote endpoints may run with optional auth and custom rate limiting.
    $quotationMiddleware = [
        'quotation.auth',
        'throttle:'.config('quotations.rate_limit', '60,1'),
    ];

    Route::middleware($quotationMiddleware)->group(function () {
        Route::get('/quotation/{symbol}', [QuotationController::class, 'show']);
        Route::post('/quotation/{symbol}', [QuotationController::class, 'store']);
        Route::get('/quotations', [QuotationController::class, 'index']);
        Route::post('/quotations/bulk-delete', [QuotationController::class, 'destroyBatch'])
            ->middleware('quotation.admin');
        Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])
            ->whereNumber('quotation')
            ->middleware('quotation.admin');
    });
});
