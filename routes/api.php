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

    Route::apiResource('services', ServiceController::class);
    Route::apiResource('endpoints', EndpointController::class);
    Route::apiResource('mock-responses', MockResponseController::class);
    Route::apiResource('scheduled-webhooks', ScheduledWebhookController::class);

    // Logs are read-only
    Route::get('logs', [RequestLogController::class, 'index'])->name('logs.index');
    Route::get('logs/{log}', [RequestLogController::class, 'show'])->name('logs.show');
    Route::delete('logs', [RequestLogController::class, 'destroyAll'])->name('logs.destroy-all');
});
