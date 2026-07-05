<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Resources\ServiceConfigResource;
use App\Mcp\Servers\MockwaveServer;
use App\Mcp\Tools\CreateEndpointTool;
use App\Mcp\Tools\CreateServiceTool;
use App\Mcp\Tools\GetEndpointsTool;
use App\Mcp\Tools\ListServicesTool;
use App\Mcp\Tools\SwitchModeTool;
use App\Mcp\Tools\UpsertMockResponseTool;
use App\Models\Endpoint;
use App\Models\MockResponse;
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
        $admin = $this->adminWithWriteToken();
        $service = Service::factory()->create(['slug' => 'bank-api', 'mode' => 'mock']);

        MockwaveServer::actingAs($admin)
            ->tool(SwitchModeTool::class, ['slug' => 'bank-api', 'mode' => 'proxy'])
            ->assertOk();

        $this->assertSame('proxy', $service->fresh()->mode);
    }

    public function test_non_admin_cannot_switch_mode(): void
    {
        $user = User::factory()->create();
        $user->withAccessToken(
            $user->createToken('mcp-test', ['mcp:read', 'mcp:write'])->accessToken
        );
        $service = Service::factory()->create(['slug' => 'bank-api', 'mode' => 'mock']);

        MockwaveServer::actingAs($user)
            ->tool(SwitchModeTool::class, ['slug' => 'bank-api', 'mode' => 'proxy'])
            ->assertHasErrors();

        $this->assertSame('mock', $service->fresh()->mode);
    }

    public function test_admin_can_create_service(): void
    {
        $admin = $this->adminWithWriteToken();

        MockwaveServer::actingAs($admin)
            ->tool(CreateServiceTool::class, [
                'name' => 'Payments API',
                'slug' => 'payments-api',
                'description' => 'Payment provider mocks',
            ])
            ->assertOk()
            ->assertSee('payments-api');

        $service = Service::where('slug', 'payments-api')->firstOrFail();

        $this->assertSame('Payments API', $service->name);
        $this->assertSame('mock', $service->mode);
        $this->assertTrue($service->is_active);
    }

    public function test_admin_can_create_endpoint(): void
    {
        $admin = $this->adminWithWriteToken();
        $service = Service::factory()->create(['slug' => 'payments-api', 'mode' => 'mock']);

        MockwaveServer::actingAs($admin)
            ->tool(CreateEndpointTool::class, [
                'serviceSlug' => 'payments-api',
                'method' => 'POST',
                'path' => '/v1/payments',
            ])
            ->assertOk()
            ->assertSee('/gateway/payments-api/v1/payments');

        $endpoint = Endpoint::whereBelongsTo($service)->firstOrFail();

        $this->assertSame('POST', $endpoint->method);
        $this->assertSame('/v1/payments', $endpoint->path);
        $this->assertTrue($endpoint->is_active);
    }

    public function test_create_endpoint_rejects_duplicate_method_and_path(): void
    {
        $admin = $this->adminWithWriteToken();
        $service = Service::factory()->create(['slug' => 'payments-api']);
        Endpoint::factory()->create([
            'service_id' => $service->id,
            'method' => 'POST',
            'path' => '/v1/payments',
        ]);

        MockwaveServer::actingAs($admin)
            ->tool(CreateEndpointTool::class, [
                'serviceSlug' => 'payments-api',
                'method' => 'POST',
                'path' => '/v1/payments',
            ])
            ->assertHasErrors(['already exists']);

        $this->assertSame(1, $service->endpoints()->count());
    }

    public function test_admin_can_create_and_update_mock_response(): void
    {
        $admin = $this->adminWithWriteToken();
        $endpoint = Endpoint::factory()->create();

        MockwaveServer::actingAs($admin)
            ->tool(UpsertMockResponseTool::class, [
                'endpointId' => $endpoint->id,
                'statusCode' => 201,
                'body' => ['id' => 42, 'status' => 'created'],
                'headers' => ['X-Mock' => 'true'],
                'delayMs' => 100,
            ])
            ->assertOk()
            ->assertSee('"action":"created"');

        $mockResponse = MockResponse::where('endpoint_id', $endpoint->id)->firstOrFail();

        $this->assertSame(201, $mockResponse->status_code);
        $this->assertSame(['id' => 42, 'status' => 'created'], $mockResponse->body);
        $this->assertSame(['X-Mock' => 'true'], $mockResponse->headers);
        $this->assertSame(100, $mockResponse->delay_ms);

        MockwaveServer::actingAs($admin)
            ->tool(UpsertMockResponseTool::class, [
                'endpointId' => $endpoint->id,
                'statusCode' => 202,
            ])
            ->assertOk()
            ->assertSee('"action":"updated"');

        $mockResponse->refresh();

        $this->assertSame(202, $mockResponse->status_code);
        $this->assertSame(['id' => 42, 'status' => 'created'], $mockResponse->body);
        $this->assertSame(1, MockResponse::where('endpoint_id', $endpoint->id)->count());
    }

    public function test_non_admin_cannot_use_mock_write_tools(): void
    {
        $user = User::factory()->create();
        $user->withAccessToken(
            $user->createToken('mcp-test', ['mcp:read', 'mcp:write'])->accessToken
        );
        $endpoint = Endpoint::factory()->create();

        MockwaveServer::actingAs($user)
            ->tool(CreateServiceTool::class, [
                'name' => 'Forbidden API',
                'slug' => 'forbidden-api',
            ])
            ->assertHasErrors();

        MockwaveServer::actingAs($user)
            ->tool(CreateEndpointTool::class, [
                'serviceSlug' => $endpoint->service->slug,
                'method' => 'GET',
                'path' => '/forbidden',
            ])
            ->assertHasErrors();

        MockwaveServer::actingAs($user)
            ->tool(UpsertMockResponseTool::class, [
                'endpointId' => $endpoint->id,
                'statusCode' => 200,
            ])
            ->assertHasErrors();

        $this->assertFalse(Service::where('slug', 'forbidden-api')->exists());
        $this->assertFalse(MockResponse::where('endpoint_id', $endpoint->id)->exists());
    }

    public function test_admin_without_mcp_write_ability_cannot_use_write_tools(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->withAccessToken(
            $admin->createToken('read-only', ['mcp:read'])->accessToken
        );

        MockwaveServer::actingAs($admin)
            ->tool(CreateServiceTool::class, [
                'name' => 'Forbidden API',
                'slug' => 'forbidden-api',
            ])
            ->assertHasErrors();

        $this->assertFalse(Service::where('slug', 'forbidden-api')->exists());
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

    private function adminWithWriteToken(): User
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('mcp-test', ['mcp:read', 'mcp:write']);

        return $admin->withAccessToken($token->accessToken);
    }
}
