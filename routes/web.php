<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EndpointController;
use App\Http\Controllers\MockGatewayController;
use App\Http\Controllers\MockResponseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestLogController;
use App\Http\Controllers\ScheduledWebhookController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // ── Чтение: доступно admin и user ──
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('endpoints', [EndpointController::class, 'index'])->name('endpoints.index');
    Route::get('mock-responses', [MockResponseController::class, 'index'])->name('mock-responses.index');
    Route::get('scheduler', [ScheduledWebhookController::class, 'index'])->name('scheduler.index');
    Route::get('logs', [RequestLogController::class, 'index'])->name('logs.index');

    // ── Профиль (Breeze) ──
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Мутации: только admin ──
    Route::middleware('admin')->group(function () {
        Route::post('services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::post('endpoints', [EndpointController::class, 'store'])->name('endpoints.store');
        Route::put('endpoints/{endpoint}', [EndpointController::class, 'update'])->name('endpoints.update');
        Route::delete('endpoints/{endpoint}', [EndpointController::class, 'destroy'])->name('endpoints.destroy');

        Route::post('mock-responses', [MockResponseController::class, 'store'])->name('mock-responses.store');
        Route::delete('mock-responses/{mock_response}', [MockResponseController::class, 'destroy'])->name('mock-responses.destroy');

        Route::post('scheduler', [ScheduledWebhookController::class, 'store'])->name('scheduler.store');
        Route::put('scheduler/{scheduled_webhook}', [ScheduledWebhookController::class, 'update'])->name('scheduler.update');
        Route::delete('scheduler/{scheduled_webhook}', [ScheduledWebhookController::class, 'destroy'])->name('scheduler.destroy');

        Route::delete('logs', [RequestLogController::class, 'destroyAll'])->name('logs.destroy-all');
    });
});

require __DIR__.'/auth.php';

// ── Gateway — единая публичная точка входа (без auth, метод любой) ──
// Должен идти последним: catch-all по {path}, ограничен префиксом /gateway/.
Route::any('/gateway/{service_slug}/{path?}', [MockGatewayController::class, 'handle'])
    ->where('path', '.*')
    ->name('gateway');
