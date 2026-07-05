<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateMcpAgentCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_admin_agent_and_token(): void
    {
        $this->artisan('mockwave:mcp-agent', [
            'email' => 'agent@example.com',
            '--name' => 'Test MCP Agent',
        ])
            ->expectsOutputToContain('is ready with role admin')
            ->expectsOutputToContain('will not be shown again')
            ->assertSuccessful();

        $agent = User::where('email', 'agent@example.com')->firstOrFail();
        $token = $agent->tokens()->firstOrFail();

        $this->assertSame('Test MCP Agent', $agent->name);
        $this->assertSame(UserRole::Admin, $agent->role);
        $this->assertSame(['mcp:read', 'mcp:write'], $token->abilities);
    }

    public function test_command_refuses_to_promote_existing_user_without_option(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->artisan('mockwave:mcp-agent', ['email' => $user->email])
            ->expectsOutputToContain('Re-run with --promote')
            ->assertFailed();

        $this->assertSame(UserRole::User, $user->fresh()->role);
        $this->assertCount(0, $user->tokens);
    }

    public function test_command_can_promote_user_and_revoke_existing_tokens(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $oldTokenId = $user->createToken('old-token', ['mcp:read'])->accessToken->id;

        $this->artisan('mockwave:mcp-agent', [
            'email' => $user->email,
            '--promote' => true,
            '--revoke-existing' => true,
            '--token-name' => 'replacement-token',
        ])->assertSuccessful();

        $user->refresh();

        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertFalse($user->tokens()->whereKey($oldTokenId)->exists());
        $this->assertSame('replacement-token', $user->tokens()->sole()->name);
    }
}
