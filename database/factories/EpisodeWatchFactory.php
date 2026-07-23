<?php

namespace Database\Factories;

use App\Models\EpisodeWatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EpisodeWatch>
 */
class EpisodeWatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tmdb_id' => fake()->numberBetween(100, 999999),
            'season_number' => fake()->numberBetween(1, 10),
            'episode_number' => fake()->numberBetween(1, 24),
            'watched_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
