<?php

namespace App\Mcp\Tools;

use App\Models\Endpoint;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Read the configured mock response (status, body, headers, delay) for an endpoint id.')]
class GetMockResponseTool extends Tool
{
    public function handle(Request $request): Response
    {
        $endpoint = Endpoint::with('mockResponse')->find($request->get('endpointId'));

        if (! $endpoint?->mockResponse) {
            return Response::text('No mock response configured for this endpoint.');
        }

        return Response::text($endpoint->mockResponse->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'endpointId' => $schema->integer()
                ->description('The endpoint id whose mock response to read.')
                ->required(),
        ];
    }
}
