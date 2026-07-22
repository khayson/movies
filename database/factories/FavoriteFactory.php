<?php

namespace Database\Factories;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Favorite>
 */
class FavoriteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tmdb_id' => fake()->numberBetween(1, 999999),
            'media_type' => fake()->randomElement(['movie', 'tv']),
            'title' => fake()->sentence(3),
            'poster_path' => '/'.fake()->bothify('??????????').'.jpg',
        ];
    }
}
