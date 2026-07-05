<?php

namespace App\Mcp\Tools;

use App\Models\Service;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Switch a service between mock and proxy mode.')]
class SwitchModeTool extends AdminTool
{
    public function handle(Request $request): Response
    {
        $slug = $request->get('slug');
        $mode = $request->get('mode');

        $service = Service::where('slug', $slug)->first();

        if (! $service) {
            return Response::text("Service '{$slug}' not found.");
        }

        $service->update(['mode' => $mode]);

        return Response::text("Service '{$service->slug}' switched to {$mode} mode.");
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
            'mode' => $schema->string()
                ->description('Target mode: mock or proxy.')
                ->enum(['mock', 'proxy'])
                ->required(),
        ];
    }
}
