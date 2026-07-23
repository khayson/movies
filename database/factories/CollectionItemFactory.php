<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\CollectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionItem>
 */
class CollectionItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'tmdb_id' => fake()->numberBetween(100, 999999),
            'media_type' => fake()->randomElement(['movie', 'tv']),
            'title' => fake()->sentence(3),
            'poster_path' => '/'.fake()->word().'.jpg',
            'sort_order' => fake()->numberBetween(0, 100),
            'note' => fake()->optional(0.3)->sentence(),
        ];
    }
}
