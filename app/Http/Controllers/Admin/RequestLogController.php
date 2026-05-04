<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RequestLogResource;
use App\Models\RequestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequestLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $logs = RequestLog::with('endpoint.service')
            ->when($request->service_id, fn ($q) => $q->whereHas(
                'endpoint', fn ($q) => $q->where('service_id', $request->service_id)
            ))
            ->when($request->mode, fn ($q) => $q->where('mode_used', $request->mode))
            ->when($request->method, fn ($q) => $q->where('method', strtoupper($request->method)))
            ->orderByDesc('created_at')
            ->paginate(50);

        return RequestLogResource::collection($logs);
    }

    public function show(RequestLog $log): RequestLogResource
    {
        return new RequestLogResource($log->load('endpoint.service'));
    }

    public function destroyAll(): JsonResponse
    {
        RequestLog::truncate();

        return response()->json(['message' => 'All logs cleared.']);
    }
}
