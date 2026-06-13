<?php

namespace Database\Factories;

use App\Models\ScheduledWebhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduledWebhook>
 */
class ScheduledWebhookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'target_url' => fake()->url(),
            'method' => 'POST',
            'payload' => ['event' => 'demo'],
            'headers' => [],
            'cron_expression' => '*/15 * * * *',
            'is_active' => true,
            'last_run_at' => null,
        ];
    }
}
