<?php

namespace App\Mcp\Tools;

use App\Models\Service;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List all endpoints for a given service slug, including their mock responses.')]
class GetEndpointsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $slug = $request->get('slug');

        $service = Service::where('slug', $slug)->first();

        if (! $service) {
            return Response::text("Service '{$slug}' not found.");
        }

        $endpoints = $service->endpoints()
            ->with('mockResponse')
            ->orderBy('path')
            ->get();

        return Response::text($endpoints->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()
                ->description('The service slug (e.g. bank-api).')
                ->required(),
        ];
    }
}
