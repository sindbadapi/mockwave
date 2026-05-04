<?php

use App\Console\Commands\DispatchWebhooksCommand;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Run the webhook dispatcher every minute.
        // The command itself checks each webhook's cron_expression against current time.
        $schedule->command(DispatchWebhooksCommand::class)
            ->everyMinute()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/webhooks.log'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
