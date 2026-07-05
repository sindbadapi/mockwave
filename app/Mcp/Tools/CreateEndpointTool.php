<?php

namespace App\Mcp\Tools;

use App\Models\Endpoint;
use App\Models\Service;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create an endpoint for an existing Mockwave service. Admin access is required.')]
class CreateEndpointTool extends AdminTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'serviceSlug' => ['required', 'string', 'exists:services,slug'],
            'method' => ['required', Rule::in(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'ANY'])],
            'path' => ['required', 'string', 'max:500', 'starts_with:/'],
            'modeOverride' => ['nullable', Rule::in(['mock', 'proxy'])],
            'proxyUrl' => ['nullable', 'url', 'max:500'],
            'isActive' => ['sometimes', 'boolean'],
        ], [
            'serviceSlug.exists' => 'The requested service does not exist. Create it first or choose an existing slug.',
            'path.starts_with' => 'The endpoint path must start with /.',
        ]);

        $service = Service::where('slug', $validated['serviceSlug'])->firstOrFail();

        $exists = Endpoint::where('service_id', $service->id)
            ->where('method', $validated['method'])
            ->where('path', $validated['path'])
            ->exists();

        if ($exists) {
            return Response::error(
                "Endpoint {$validated['method']} {$validated['path']} already exists for service '{$service->slug}'."
            );
        }

        $endpoint = $service->endpoints()->create([
            'method' => $validated['method'],
            'path' => $validated['path'],
            'mode_override' => $validated['modeOverride'] ?? null,
            'proxy_url' => $validated['proxyUrl'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
        ]);

        return Response::json([
            'endpointId' => $endpoint->id,
            'serviceSlug' => $service->slug,
            'method' => $endpoint->method,
            'path' => $endpoint->path,
            'resolvedMode' => $endpoint->resolvedMode(),
            'isActive' => $endpoint->is_active,
            'gatewayUrl' => "/gateway/{$service->slug}".($endpoint->path === '/' ? '' : $endpoint->path),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'serviceSlug' => $schema->string()
                ->description('Slug of the existing service.')
                ->required(),
            'method' => $schema->string()
                ->enum(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'ANY'])
                ->description('HTTP method matched by the endpoint.')
                ->required(),
            'path' => $schema->string()
                ->description('Path beginning with /, for example /v1/payments.')
                ->required(),
            'modeOverride' => $schema->string()
                ->enum(['mock', 'proxy'])
                ->description('Optional endpoint mode override; omit to inherit the service mode.')
                ->nullable(),
            'proxyUrl' => $schema->string()
                ->format('uri')
                ->description('Optional endpoint-specific upstream URL.')
                ->nullable(),
            'isActive' => $schema->boolean()
                ->description('Whether the endpoint accepts gateway requests.')
                ->default(true),
        ];
    }
}
