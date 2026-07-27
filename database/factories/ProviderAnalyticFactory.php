<?php

namespace Database\Factories;

use App\Models\ProviderAnalytic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderAnalytic>
 */
class ProviderAnalyticFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(['CineSrc', 'VidCore', 'VidSrc', 'VidPhantom', 'SuperEmbed']),
            'region' => fake()->randomElement(['US', 'GB', 'DE', 'FR', 'JP', 'BR']),
            'hour_bucket' => fake()->numberBetween(0, 23),
            'success_count' => fake()->numberBetween(10, 500),
            'failure_count' => fake()->numberBetween(0, 50),
            'buffer_count' => fake()->numberBetween(0, 30),
            'avg_load_ms' => fake()->numberBetween(200, 8000),
            'date' => fake()->dateTimeBetween('-7 days')->format('Y-m-d'),
        ];
    }
}
