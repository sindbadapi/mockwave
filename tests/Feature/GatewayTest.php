<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\MockResponse;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_mock_endpoint_returns_configured_response(): void
    {
        $service = Service::factory()->create(['slug' => 'bank-api', 'mode' => 'mock', 'is_active' => true]);
        $endpoint = Endpoint::factory()->create([
            'service_id' => $service->id,
            'method' => 'GET',
            'path' => '/v1/accounts',
            'is_active' => true,
        ]);
        MockResponse::factory()->create([
            'endpoint_id' => $endpoint->id,
            'status_code' => 200,
            'body' => ['ok' => true],
            'delay_ms' => 0,
        ]);

        $this->getJson('/gateway/bank-api/v1/accounts')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_unknown_service_returns_404(): void
    {
        $this->getJson('/gateway/nope/x')->assertNotFound();
    }

    public function test_request_is_logged(): void
    {
        $this->withoutDefer();

        $service = Service::factory()->create(['slug' => 'bank-api', 'mode' => 'mock', 'is_active' => true]);
        Endpoint::factory()->create([
            'service_id' => $service->id, 'method' => 'GET', 'path' => '/ping', 'is_active' => true,
        ]);

        $this->getJson('/gateway/bank-api/ping');

        $this->assertDatabaseHas('request_logs', ['path' => '/ping', 'mode_used' => 'mock']);
    }

    public function test_not_found_request_is_logged(): void
    {
        $this->withoutDefer();

        $this->getJson('/gateway/missing-service/ping')->assertNotFound();

        $this->assertDatabaseHas('request_logs', [
            'path' => '/ping',
            'mode_used' => 'not_found',
        ]);
    }

    public function test_proxy_forwards_to_upstream(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'upstream.test/*' => Http::response(['proxied' => true], 200),
        ]);

        $service = Service::factory()->create([
            'slug' => 'pay', 'mode' => 'proxy', 'base_url' => 'https://upstream.test', 'is_active' => true,
        ]);
        Endpoint::factory()->create([
            'service_id' => $service->id, 'method' => 'POST', 'path' => '/charge', 'is_active' => true,
        ]);

        $this->postJson('/gateway/pay/charge', ['amount' => 100])
            ->assertOk()
            ->assertJson(['proxied' => true]);
    }

    public function test_proxy_returns_502_on_connection_failure(): void
    {
        Http::fake([
            'upstream.test/*' => Http::failedConnection(),
        ]);

        $service = Service::factory()->create([
            'slug' => 'pay', 'mode' => 'proxy', 'base_url' => 'https://upstream.test', 'is_active' => true,
        ]);
        Endpoint::factory()->create([
            'service_id' => $service->id, 'method' => 'GET', 'path' => '/ping', 'is_active' => true,
        ]);

        $this->getJson('/gateway/pay/ping')->assertStatus(502);
    }
}
