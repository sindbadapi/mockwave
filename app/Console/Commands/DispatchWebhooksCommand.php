<?php

namespace App\Console\Commands;

use App\Models\ScheduledWebhook;
use Cron\CronExpression;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DispatchWebhooksCommand extends Command
{
    protected $signature = 'mockwave:dispatch-webhooks';

    protected $description = 'Dispatch all active scheduled webhooks whose cron expression matches the current time.';

    public function __construct(private readonly Client $httpClient)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $webhooks = ScheduledWebhook::where('is_active', true)->get();

        if ($webhooks->isEmpty()) {
            $this->info('No active webhooks to dispatch.');

            return self::SUCCESS;
        }

        foreach ($webhooks as $webhook) {
            if (! $this->cronMatches($webhook->cron_expression)) {
                continue;
            }

            $this->dispatchWebhook($webhook);
        }

        return self::SUCCESS;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Check whether the cron expression matches the current minute.
     *
     * We use Symfony's CronExpression (bundled with Laravel) for parsing.
     */
    private function cronMatches(string $expression): bool
    {
        try {
            $cron = new CronExpression($expression);

            return $cron->isDue();
        } catch (\InvalidArgumentException $e) {
            Log::warning("[Webhooks] Invalid cron expression: {$expression}", ['error' => $e->getMessage()]);

            return false;
        }
    }

    private function dispatchWebhook(ScheduledWebhook $webhook): void
    {
        $this->line("→ Dispatching [{$webhook->name}] to {$webhook->target_url}");

        $options = [
            'timeout' => 15,
            'http_errors' => false,
            'headers' => array_merge(
                ['Content-Type' => 'application/json'],
                $webhook->headers ?? [],
            ),
        ];

        if (! empty($webhook->payload)) {
            $options['json'] = $webhook->payload;
        }

        try {
            $response = $this->httpClient->request($webhook->method, $webhook->target_url, $options);

            $webhook->update(['last_run_at' => now()]);

            $this->info("  ✓ {$response->getStatusCode()}");

            Log::info('[Webhooks] Dispatched', [
                'webhook' => $webhook->name,
                'url' => $webhook->target_url,
                'status' => $response->getStatusCode(),
            ]);
        } catch (GuzzleException $e) {
            $this->error("  ✗ Failed: {$e->getMessage()}");

            Log::error('[Webhooks] Dispatch failed', [
                'webhook' => $webhook->name,
                'url' => $webhook->target_url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
