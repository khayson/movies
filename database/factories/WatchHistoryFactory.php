<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WatchHistory>
 */
class WatchHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $duration = fake()->numberBetween(3600, 10800);

        return [
            'user_id' => User::factory(),
            'tmdb_id' => fake()->numberBetween(1, 999999),
            'media_type' => fake()->randomElement(['movie', 'tv']),
            'title' => fake()->sentence(3),
            'poster_path' => '/'.fake()->bothify('??????????').'.jpg',
            'progress_seconds' => fake()->numberBetween(0, $duration),
            'duration_seconds' => $duration,
            'season' => null,
            'episode' => null,
        ];
    }
}
