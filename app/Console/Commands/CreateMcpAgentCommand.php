<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateMcpAgentCommand extends Command
{
    protected $signature = 'mockwave:mcp-agent
        {email : Email address used to identify the MCP agent}
        {--name=Mockwave MCP Agent : Display name for a new agent user}
        {--token-name=mockwave-mcp : Name stored with the Sanctum token}
        {--promote : Promote an existing non-admin user}
        {--revoke-existing : Revoke all existing tokens for this user first}';

    protected $description = 'Create an admin MCP agent user and issue a Sanctum token.';

    public function handle(): int
    {
        $input = [
            'email' => $this->argument('email'),
            'name' => $this->option('name'),
            'token_name' => $this->option('token-name'),
        ];

        $validator = Validator::make($input, [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'token_name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::INVALID;
        }

        $user = User::where('email', $input['email'])->first();

        if ($user && ! $user->isAdmin() && ! $this->option('promote')) {
            $this->error('This email belongs to a non-admin user. Re-run with --promote to grant administrative access.');

            return self::FAILURE;
        }

        if (! $user) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Str::password(64),
                'role' => UserRole::Admin,
            ]);
        } elseif (! $user->isAdmin()) {
            $user->update(['role' => UserRole::Admin]);
        }

        if ($this->option('revoke-existing')) {
            $user->tokens()->delete();
        }

        $token = $user->createToken(
            $input['token_name'],
            ['mcp:read', 'mcp:write'],
        );

        $this->info("MCP agent '{$user->email}' is ready with role admin.");
        $this->warn('Copy this token now. It will not be shown again:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
