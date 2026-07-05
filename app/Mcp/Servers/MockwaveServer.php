<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\RequestLogResource;
use App\Mcp\Resources\ServiceConfigResource;
use App\Mcp\Tools\CreateEndpointTool;
use App\Mcp\Tools\CreateServiceTool;
use App\Mcp\Tools\GetEndpointsTool;
use App\Mcp\Tools\GetMockResponseTool;
use App\Mcp\Tools\GetRequestLogsTool;
use App\Mcp\Tools\ListServicesTool;
use App\Mcp\Tools\SwitchModeTool;
use App\Mcp\Tools\UpsertMockResponseTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Mockwave')]
#[Version('1.0.0')]
#[Instructions('Inspect and manage mock services. Administrators can create services and endpoints, configure mock responses, and switch mock/proxy mode.')]
class MockwaveServer extends Server
{
    protected array $tools = [
        ListServicesTool::class,
        GetEndpointsTool::class,
        GetRequestLogsTool::class,
        GetMockResponseTool::class,
        CreateServiceTool::class,
        CreateEndpointTool::class,
        UpsertMockResponseTool::class,
        SwitchModeTool::class,
    ];

    protected array $resources = [
        ServiceConfigResource::class,
        RequestLogResource::class,
    ];

    protected array $prompts = [];
}
