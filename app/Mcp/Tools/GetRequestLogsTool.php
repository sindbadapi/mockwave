<?php

namespace App\Mcp\Tools;

use App\Models\RequestLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Fetch the most recent request logs for a given endpoint id.')]
class GetRequestLogsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $limit = (int) ($request->get('limit') ?? 20);

        $logs = RequestLog::where('endpoint_id', $request->get('endpointId'))
            ->latest('created_at')
            ->limit($limit)
            ->get(['method', 'path', 'mode_used', 'duration_ms', 'created_at']);

        return Response::text($logs->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'endpointId' => $schema->integer()
                ->description('The endpoint id to fetch logs for.')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of logs to return (default 20).'),
        ];
    }
}
