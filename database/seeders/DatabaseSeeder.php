<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Endpoint;
use App\Models\MockResponse;
use App\Models\ScheduledWebhook;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin user ────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@mockwave.local')],
            [
                'name' => 'Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role' => UserRole::Admin,
            ],
        );

        // ── Demo: Bank API (mock mode) ─────────────────────────────────────────
        $bankService = Service::firstOrCreate(
            ['slug' => 'bank-api'],
            [
                'name' => 'Bank API',
                'base_url' => 'https://api.real-bank.example.com',
                'description' => 'Demo banking microservice — mock mode.',
                'mode' => 'mock',
                'is_active' => true,
            ],
        );

        // GET /v1/accounts
        $accountsEndpoint = Endpoint::firstOrCreate(
            ['service_id' => $bankService->id, 'method' => 'GET', 'path' => '/v1/accounts'],
            ['mode_override' => null, 'is_active' => true],
        );

        MockResponse::firstOrCreate(
            ['endpoint_id' => $accountsEndpoint->id],
            [
                'status_code' => 200,
                'body' => [
                    'accounts' => [
                        ['id' => 'acc_001', 'currency' => 'RUB', 'balance' => 150000.00],
                        ['id' => 'acc_002', 'currency' => 'USD', 'balance' => 1200.50],
                    ],
                ],
                'headers' => ['X-Mock-Source' => 'Mockwave'],
                'delay_ms' => 120,
            ],
        );

        // POST /v1/transfer
        $transferEndpoint = Endpoint::firstOrCreate(
            ['service_id' => $bankService->id, 'method' => 'POST', 'path' => '/v1/transfer'],
            ['mode_override' => null, 'is_active' => true],
        );

        MockResponse::firstOrCreate(
            ['endpoint_id' => $transferEndpoint->id],
            [
                'status_code' => 201,
                'body' => ['transaction_id' => 'txn_demo_001', 'status' => 'pending'],
                'headers' => [],
                'delay_ms' => 300,
            ],
        );

        // GET /v1/accounts — 404 variant endpoint (inactive, for reference)
        $notFoundEndpoint = Endpoint::firstOrCreate(
            ['service_id' => $bankService->id, 'method' => 'GET', 'path' => '/v1/not-found-example'],
            ['mode_override' => null, 'is_active' => false],
        );

        MockResponse::firstOrCreate(
            ['endpoint_id' => $notFoundEndpoint->id],
            [
                'status_code' => 404,
                'body' => ['error' => 'Account not found.'],
                'headers' => [],
                'delay_ms' => 50,
            ],
        );

        // ── Demo: Payment Service (proxy mode) ────────────────────────────────
        $paymentService = Service::firstOrCreate(
            ['slug' => 'payment-service'],
            [
                'name' => 'Payment Service',
                'base_url' => 'https://payments.example.com',
                'description' => 'Proxied payment gateway — requests go to real service.',
                'mode' => 'proxy',
                'is_active' => true,
            ],
        );

        Endpoint::firstOrCreate(
            ['service_id' => $paymentService->id, 'method' => 'POST', 'path' => '/charge'],
            ['mode_override' => null, 'proxy_url' => null, 'is_active' => true],
        );

        // ── Demo: Scheduled Webhook ───────────────────────────────────────────
        ScheduledWebhook::firstOrCreate(
            ['name' => 'Demo Bank Notification'],
            [
                'target_url' => 'http://localhost:8080/gateway/bank-api/v1/accounts',
                'method' => 'POST',
                'payload' => ['event' => 'balance_updated', 'account_id' => 'acc_001'],
                'headers' => ['X-Webhook-Source' => 'Mockwave-Scheduler'],
                'cron_expression' => '*/15 * * * *',
                'is_active' => false, // disabled by default — enable in admin panel
            ],
        );
    }
}
