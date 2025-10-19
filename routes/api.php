<?php

use App\Http\Controllers\Api\AbandonedOrdersController;
use App\Http\Controllers\Api\CompletedOrdersController;
use App\Http\Controllers\Api\DispatchOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->group(function (): void {
    Route::get('completed', [CompletedOrdersController::class, 'index']);
    Route::get('abandoned', [AbandonedOrdersController::class, 'index']);

    Route::post('{orderId}/dispatch', DispatchOrderController::class)
        ->middleware('throttle:dispatches');
});

