<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tmdb_id' => fake()->numberBetween(100, 999999),
            'media_type' => fake()->randomElement(['movie', 'tv']),
            'title' => fake()->sentence(4),
            'rating' => fake()->numberBetween(1, 10),
            'body' => fake()->optional(0.8)->paragraphs(2, true),
            'contains_spoilers' => fake()->boolean(20),
            'helpful_count' => fake()->numberBetween(0, 50),
        ];
    }

    public function spoiler(): static
    {
        return $this->state(['contains_spoilers' => true]);
    }
}
