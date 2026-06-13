<?php

namespace App\Http\Controllers;

use App\Http\Resources\RequestLogResource;
use App\Models\RequestLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequestLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = RequestLog::with('endpoint.service')
            ->when($request->input('mode'), fn ($q, $mode) => $q->where('mode_used', $mode))
            ->when($request->input('method'), fn ($q, $method) => $q->where('method', strtoupper((string) $method)))
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('Logs/Index', [
            'logs' => RequestLogResource::collection($logs),
            'filters' => $request->only(['mode', 'method']),
        ]);
    }

    public function destroyAll(): RedirectResponse
    {
        RequestLog::truncate();

        return back()->with('success', 'All logs cleared.');
    }
}
