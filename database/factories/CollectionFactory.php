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
     * @var array<int, array{name: string, description: string}>
     */
    private static array $collections = [
        ['name' => 'Mind-Bending Sci-Fi', 'description' => 'Films that make you question reality'],
        ['name' => 'Weekend Comfort Watches', 'description' => 'Perfect for lazy Sundays'],
        ['name' => 'Award Winners That Deserve It', 'description' => 'Oscar and festival darlings that lived up to the hype'],
        ['name' => 'Shows I Binged in One Weekend', 'description' => 'No regrets'],
        ['name' => 'Classic Cinema Essentials', 'description' => 'Films every cinephile must see'],
        ['name' => 'Feel-Good Movie Night', 'description' => 'For when you need a pick-me-up'],
        ['name' => 'International Gems', 'description' => 'Best non-English language films'],
        ['name' => 'Gritty and Raw', 'description' => 'Unflinching films that don\'t hold back'],
        ['name' => 'Animated Masterpieces', 'description' => 'Animation is cinema'],
        ['name' => 'Director Spotlight: Nolan', 'description' => 'Every Christopher Nolan film ranked'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $collection = fake()->randomElement(self::$collections);

        return [
            'user_id' => User::factory(),
            'name' => $collection['name'],
            'description' => $collection['description'],
            'is_public' => fake()->boolean(80),
            'slug' => Str::slug($collection['name']).'-'.fake()->unique()->randomNumber(5),
        ];
    }

    public function private(): static
    {
        return $this->state(['is_public' => false]);
    }
}
