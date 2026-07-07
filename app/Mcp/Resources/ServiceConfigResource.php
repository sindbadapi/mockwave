<?php

namespace App\Mcp\Resources;

use App\Models\Service;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Description('Full configuration for a Mockwave service and its endpoints.')]
#[MimeType('application/json')]
class ServiceConfigResource extends Resource implements HasUriTemplate
{
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('mockwave://services/{slug}');
    }

    public function handle(Request $request): Response
    {
        $service = Service::with(['endpoints.mockResponse'])
            ->where('slug', $request->get('slug'))
            ->firstOrFail();

        return Response::text((string) json_encode([
            ...$service->toArray(),
            'gateway_prefix' => $service->gatewayPathPrefix(),
            'gateway_base_uri' => $service->gatewayBaseUri(),
            'gateway_client_hint' => 'Use gateway_base_uri as Guzzle base_uri and send endpoint paths without a leading slash.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
