<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\RequestLogResource;
use App\Mcp\Resources\ServiceConfigResource;
use App\Mcp\Tools\GetEndpointsTool;
use App\Mcp\Tools\GetMockResponseTool;
use App\Mcp\Tools\GetRequestLogsTool;
use App\Mcp\Tools\ListServicesTool;
use App\Mcp\Tools\SwitchModeTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Mockwave')]
#[Version('1.0.0')]
#[Instructions('Manage mock services: list services, inspect endpoints, switch mock/proxy mode, and read request logs.')]
class MockwaveServer extends Server
{
    protected array $tools = [
        ListServicesTool::class,
        GetEndpointsTool::class,
        SwitchModeTool::class,
        GetRequestLogsTool::class,
        GetMockResponseTool::class,
    ];

    protected array $resources = [
        ServiceConfigResource::class,
        RequestLogResource::class,
    ];

    protected array $prompts = [];
}
