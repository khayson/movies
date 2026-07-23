<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'description' => fake()->optional(0.7)->sentence(),
            'is_public' => fake()->boolean(80),
            'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
        ];
    }

    public function private(): static
    {
        return $this->state(['is_public' => false]);
    }
}
