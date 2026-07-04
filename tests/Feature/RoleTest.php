<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_helper_reflects_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
    }

    public function test_admin_middleware_blocks_non_admin(): void
    {
        Route::middleware(['web', 'auth', 'admin'])->get('/__test-admin-only', fn () => 'ok');

        $this->actingAs(User::factory()->create())
            ->get('/__test-admin-only')
            ->assertForbidden();
    }

    public function test_admin_middleware_allows_admin(): void
    {
        Route::middleware(['web', 'auth', 'admin'])->get('/__test-admin-only', fn () => 'ok');

        $this->actingAs(User::factory()->admin()->create())
            ->get('/__test-admin-only')
            ->assertOk();
    }

    public function test_admin_api_blocks_non_admin_mutations(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/admin/services', [
                'name' => 'Bank API',
                'slug' => 'bank-api',
                'mode' => 'mock',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('services', ['slug' => 'bank-api']);
    }

    public function test_admin_api_allows_admin_mutations(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/admin/services', [
                'name' => 'Bank API',
                'slug' => 'bank-api',
                'mode' => 'mock',
            ])
            ->assertCreated();

        $this->assertModelExists(Service::where('slug', 'bank-api')->firstOrFail());
    }
}
