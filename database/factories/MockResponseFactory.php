<?php

namespace Database\Factories;

use App\Models\Endpoint;
use App\Models\MockResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MockResponse>
 */
class MockResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'endpoint_id' => Endpoint::factory(),
            'status_code' => 200,
            'body' => ['ok' => true],
            'headers' => [],
            'delay_ms' => 0,
        ];
    }
}
