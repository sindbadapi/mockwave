<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Endpoint;
use App\Models\RequestLog;
use App\Models\ScheduledWebhook;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'services' => Service::count(),
                'endpoints' => Endpoint::count(),
                'logs_today' => RequestLog::whereDate('created_at', today())->count(),
                'webhooks' => ScheduledWebhook::where('is_active', true)->count(),
            ],
        ]);
    }
}
