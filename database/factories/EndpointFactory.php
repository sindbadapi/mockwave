<?php

namespace Database\Factories;

use App\Models\Endpoint;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Endpoint>
 */
class EndpointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'method' => 'GET',
            'path' => '/'.fake()->unique()->slug(2),
            'mode_override' => null,
            'proxy_url' => null,
            'is_active' => true,
        ];
    }
}
