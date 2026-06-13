<?php

namespace App\Mcp\Resources;

use App\Models\RequestLog;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Recent request logs for a given endpoint id.')]
#[MimeType('application/json')]
class RequestLogResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('mockwave://logs/{endpointId}');
    }

    public function handle(Request $request): Response
    {
        $logs = RequestLog::where('endpoint_id', $request->get('endpointId'))
            ->latest('created_at')
            ->limit(50)
            ->get(['method', 'path', 'mode_used', 'duration_ms', 'created_at']);

        return Response::text($logs->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
