<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_user_can_view_services_index(): void
    {
        Service::factory()->count(2)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('services.index'))->assertOk();

        $this->actingAs(User::factory()->create())
            ->get(route('services.index'))->assertOk();
    }

    public function test_guest_cannot_view_services_index(): void
    {
        $this->get(route('services.index'))->assertRedirect('/login');
    }

    public function test_all_panel_index_pages_render(): void
    {
        $user = User::factory()->create();

        foreach (['dashboard', 'services.index', 'endpoints.index', 'mock-responses.index', 'scheduler.index', 'logs.index'] as $name) {
            $this->actingAs($user)->get(route($name))->assertOk();
        }
    }

    public function test_admin_can_create_service(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('services.store'), [
                'name' => 'Bank API',
                'slug' => 'bank-api',
                'mode' => 'mock',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('services', ['slug' => 'bank-api']);
    }

    public function test_user_cannot_create_service(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('services.store'), [
                'name' => 'Bank API',
                'slug' => 'bank-api',
                'mode' => 'mock',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('services', ['slug' => 'bank-api']);
    }

    public function test_admin_can_update_and_delete_service(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create(['name' => 'Old']);

        $this->actingAs($admin)
            ->put(route('services.update', $service), ['name' => 'New'])
            ->assertRedirect();
        $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'New']);

        $this->actingAs($admin)
            ->delete(route('services.destroy', $service))
            ->assertRedirect();
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_user_cannot_clear_logs(): void
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('logs.destroy-all'))
            ->assertForbidden();
    }
}
