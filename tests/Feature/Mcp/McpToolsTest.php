<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Resources\ServiceConfigResource;
use App\Mcp\Servers\MockwaveServer;
use App\Mcp\Tools\GetEndpointsTool;
use App\Mcp\Tools\ListServicesTool;
use App\Mcp\Tools\SwitchModeTool;
use App\Models\Endpoint;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Tests\TestCase;

class McpToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_services_tool_returns_services(): void
    {
        Service::factory()->create(['slug' => 'bank-api', 'name' => 'Bank API']);

        MockwaveServer::tool(ListServicesTool::class)
            ->assertOk()
            ->assertSee('bank-api');
    }

    public function test_get_endpoints_tool_returns_endpoints(): void
    {
        $service = Service::factory()->create(['slug' => 'bank-api']);
        Endpoint::factory()->create(['service_id' => $service->id, 'path' => '/v1/accounts']);

        MockwaveServer::tool(GetEndpointsTool::class, ['slug' => 'bank-api'])
            ->assertOk()
            ->assertSee('/v1/accounts');
    }

    public function test_admin_can_switch_mode(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['slug' => 'bank-api', 'mode' => 'mock']);

        MockwaveServer::actingAs($admin)
            ->tool(SwitchModeTool::class, ['slug' => 'bank-api', 'mode' => 'proxy'])
            ->assertOk();

        $this->assertSame('proxy', $service->fresh()->mode);
    }

    public function test_non_admin_cannot_switch_mode(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['slug' => 'bank-api', 'mode' => 'mock']);

        MockwaveServer::actingAs($user)
            ->tool(SwitchModeTool::class, ['slug' => 'bank-api', 'mode' => 'proxy'])
            ->assertHasErrors();

        $this->assertSame('mock', $service->fresh()->mode);
    }

    public function test_service_config_resource_returns_config(): void
    {
        $service = Service::factory()->create(['slug' => 'bank-api']);
        Endpoint::factory()->create(['service_id' => $service->id, 'path' => '/v1/accounts']);

        // Templated resources can't substitute URI params through the test harness,
        // so invoke the handler directly with the resolved argument.
        $response = (new ServiceConfigResource)->handle(new Request(['slug' => 'bank-api']));

        $this->assertStringContainsString('/v1/accounts', (string) $response->content());
    }
}
