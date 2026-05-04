---
name: mcp-development
description: "Use this skill for Laravel 13 MCP development in Mockwave. Trigger when creating or editing MCP tools, resources, prompts, or the server class under app/Mcp/. Covers: artisan make:mcp-* generators, routes/ai.php registration, Tool/Resource/Prompt class structure, correct namespaces (Laravel\\Mcp\\Request — NOT Laravel\\Mcp\\Server\\Request), schema() definition, shouldRegister(), HasUriTemplate for dynamic resources, auth:sanctum middleware, and MCP debugging. Do not use for the gateway, admin panel, or non-MCP features."
license: MIT
metadata:
  author: mockwave
---

# MCP Development — Mockwave Edition

Laravel 13 ships a first-party MCP module. This skill covers building the Mockwave MCP server that exposes mock service internals to AI clients (Claude, Cursor, etc.).

> **Always verify API details with Context7 before writing code:**
> `query-docs` with libraryId `/laravel/docs/__branch__13.x` and query describing the MCP feature needed.

---

## Critical: Correct Namespaces

The archived skill contained wrong namespaces. Use these:

```php
// CORRECT
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Support\UriTemplate;
use Illuminate\Contracts\JsonSchema\JsonSchema;

// WRONG — do not use
use Laravel\Mcp\Server\Request;   // ← does not exist
use Laravel\Mcp\Server\Response;  // ← does not exist
```

---

## Mockwave MCP Server Plan

The Mockwave MCP server makes mock service configuration and logs accessible to AI clients.

### Tools (actions)

| Class | Purpose |
|---|---|
| `ListServicesTool` | List all services with slug, mode, is_active |
| `GetEndpointsTool` | List endpoints for a given service slug |
| `SwitchModeTool` | Toggle mock/proxy on a service or endpoint |
| `GetRequestLogsTool` | Fetch recent logs for an endpoint |
| `GetMockResponseTool` | Read configured mock response body/headers/status |

### Resources (readable data)

| Class | URI | Purpose |
|---|---|---|
| `ServiceConfigResource` | `mockwave://services/{slug}` | Full config for a service + its endpoints |
| `RequestLogResource` | `mockwave://logs/{endpointId}` | Recent request logs for an endpoint |

---

## Setup

### 1. Publish routes/ai.php

```bash
php artisan vendor:publish --tag=ai-routes
```

### 2. Generate the server and primitives

```bash
php artisan make:mcp-server MockwaveServer
php artisan make:mcp-tool ListServicesTool
php artisan make:mcp-tool GetEndpointsTool
php artisan make:mcp-tool SwitchModeTool
php artisan make:mcp-tool GetRequestLogsTool
php artisan make:mcp-resource ServiceConfigResource
php artisan make:mcp-resource RequestLogResource
```

### 3. Register in routes/ai.php

```php
use App\Mcp\Servers\MockwaveServer;
use Laravel\Mcp\Facades\Mcp;

// Do NOT call Mcp::oauthRoutes() here unless adding OAuth 2.1.
// auth:sanctum is sufficient for Mockwave's admin-only MCP access.

Mcp::web('/mcp', MockwaveServer::class)
    ->middleware(['auth:sanctum']);
```

> **Do not** register `ai.php` in `bootstrap/app.php` — Laravel 13 auto-registers it.

---

## Server Class

```php
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
```

---

## Tool Example — SwitchModeTool

```php
<?php

namespace App\Mcp\Tools;

use App\Models\Service;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Switch a service between mock and proxy mode.')]
class SwitchModeTool extends Tool
{
    public function handle(Request $request): Response
    {
        $service = Service::where('slug', $request->get('slug'))->firstOrFail();
        $mode = $request->get('mode'); // 'mock' or 'proxy'

        $service->update(['mode' => $mode]);

        // Invalidate cached endpoint resolution
        cache()->tags("service:{$service->slug}")->flush();

        return Response::text("Service '{$service->slug}' switched to {$mode} mode.");
    }

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
```

---

## Resource Example — ServiceConfigResource (with URI template)

```php
<?php

namespace App\Mcp\Resources;

use App\Models\Service;
use Illuminate\Contracts\JsonSchema\JsonSchema;
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
        $slug = $request->get('slug');

        $service = Service::with(['endpoints.mockResponse'])
            ->where('slug', $slug)
            ->firstOrFail();

        return Response::text(json_encode($service->toArray(), JSON_PRETTY_PRINT));
    }
}
```

---

## Conditional Registration with shouldRegister()

Use `shouldRegister()` to restrict dangerous tools (e.g., `SwitchModeTool`) to admin users only, even when MCP is already behind `auth:sanctum`.

```php
public function shouldRegister(Request $request): bool
{
    return $request?->user()?->is_admin ?? false;
}
```

> **Важно:** Поле `is_admin` не существует в стандартной модели `User` из Laravel Breeze. Перед использованием нужно добавить миграцию и поле в модель, либо использовать другой механизм (роли, email-whitelist и т.д.).

---

## Testing MCP Tools

Test tools directly via the static `tool()` method — no HTTP needed. This project uses **PHPUnit**, not Pest:

```php
use App\Mcp\Servers\MockwaveServer;
use App\Mcp\Tools\SwitchModeTool;
use App\Models\Service;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SwitchModeToolTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_switches_service_to_proxy_mode(): void
    {
        $service = Service::factory()->create(['slug' => 'bank-api', 'mode' => 'mock']);

        MockwaveServer::tool(SwitchModeTool::class, [
            'slug' => 'bank-api',
            'mode' => 'proxy',
        ]);

        $this->assertSame('proxy', $service->fresh()->mode->value);
    }
}
```

---

## Common Pitfalls

- **Wrong namespace** — `Laravel\Mcp\Request`, not `Laravel\Mcp\Server\Request`
- **Running `mcp:start`** — it hangs waiting for stdin input; use `mcp:inspector` for debugging
- **HTTPS locally** — Node-based MCP clients (Cursor) fail with self-signed certs; use HTTP in local dev
- **No auth** — MCP exposes internal data; always apply `auth:sanctum` middleware
- **Skipping `shouldRegister()`** — destructive tools must be gated on user role
- **Not invalidating cache** — after `SwitchModeTool` runs, flush the endpoint resolution cache
- **Do not** register `routes/ai.php` in `bootstrap/app.php` — it's auto-registered
