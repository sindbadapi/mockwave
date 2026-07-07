<?php

namespace App\Mcp\Tools;

use App\Models\Service;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a Mockwave service. Admin access is required.')]
class CreateServiceTool extends AdminTool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', 'unique:services,slug'],
            'baseUrl' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'mode' => ['sometimes', Rule::in(['mock', 'proxy'])],
            'isActive' => ['sometimes', 'boolean'],
        ], [
            'slug.regex' => 'The slug may contain only lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'A service with this slug already exists. Choose another slug or use the existing service.',
        ]);

        $service = Service::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'base_url' => $validated['baseUrl'] ?? null,
            'description' => $validated['description'] ?? null,
            'mode' => $validated['mode'] ?? 'mock',
            'is_active' => $validated['isActive'] ?? true,
        ]);

        return Response::json([
            'serviceId' => $service->id,
            'name' => $service->name,
            'slug' => $service->slug,
            'mode' => $service->mode,
            'isActive' => $service->is_active,
            'gatewayPrefix' => $service->gatewayPathPrefix(),
            'gatewayBaseUri' => $service->gatewayBaseUri(),
            'gatewayClientHint' => 'Use gatewayBaseUri as Guzzle base_uri and send endpoint paths without a leading slash.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('Human-readable service name.')
                ->required(),
            'slug' => $schema->string()
                ->description('Unique lowercase URL slug, for example payment-api.')
                ->required(),
            'baseUrl' => $schema->string()
                ->format('uri')
                ->description('Optional upstream base URL for proxy mode.')
                ->nullable(),
            'description' => $schema->string()
                ->description('Optional service description.')
                ->nullable(),
            'mode' => $schema->string()
                ->enum(['mock', 'proxy'])
                ->description('Default service mode.')
                ->default('mock'),
            'isActive' => $schema->boolean()
                ->description('Whether the service accepts gateway requests.')
                ->default(true),
        ];
    }
}
