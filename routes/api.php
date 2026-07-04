<?php

use App\Http\Controllers\Admin\EndpointController;
use App\Http\Controllers\Admin\MockResponseController;
use App\Http\Controllers\Admin\RequestLogController;
use App\Http\Controllers\Admin\ScheduledWebhookController;
use App\Http\Controllers\Admin\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
| All routes require authentication (session cookie from web guard).
| The frontend SPA communicates via these JSON endpoints.
*/

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    // Read access is available to all authenticated users.
    Route::apiResource('services', ServiceController::class)->only(['index', 'show']);
    Route::apiResource('endpoints', EndpointController::class)->only(['index', 'show']);
    Route::apiResource('mock-responses', MockResponseController::class)->only(['index', 'show']);
    Route::apiResource('scheduled-webhooks', ScheduledWebhookController::class)->only(['index', 'show']);

    Route::get('logs', [RequestLogController::class, 'index'])->name('logs.index');
    Route::get('logs/{log}', [RequestLogController::class, 'show'])->name('logs.show');

    // Mutations require full administrative rights.
    Route::middleware('admin')->group(function () {
        Route::apiResource('services', ServiceController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('endpoints', EndpointController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('mock-responses', MockResponseController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('scheduled-webhooks', ScheduledWebhookController::class)->only(['store', 'update', 'destroy']);

        Route::delete('logs', [RequestLogController::class, 'destroyAll'])->name('logs.destroy-all');
    });
});
