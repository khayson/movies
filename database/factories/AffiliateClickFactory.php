<?php

namespace Database\Factories;

use App\Models\AffiliateClick;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffiliateClick>
 */
class AffiliateClickFactory extends Factory
{
    /**
     * @var array<int, array{name: string, id: string}>
     */
    private static array $services = [
        ['name' => 'Netflix', 'id' => 'netflix'],
        ['name' => 'Amazon Prime Video', 'id' => 'prime'],
        ['name' => 'Disney+', 'id' => 'disney'],
        ['name' => 'Hulu', 'id' => 'hulu'],
        ['name' => 'Apple TV+', 'id' => 'apple'],
        ['name' => 'HBO Max', 'id' => 'hbo'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = fake()->randomElement(self::$services);
        $tmdbId = fake()->randomElement([550, 680, 278, 155, 27205, 496243, 872585, 1396, 66732, 100088]);

        return [
            'user_id' => User::factory(),
            'service_name' => $service['name'],
            'service_id' => $service['id'],
            'tmdb_id' => $tmdbId,
            'media_type' => fake()->randomElement(['movie', 'tv']),
            'link' => 'https://click.justwatch.com/a?r=https%3A%2F%2Fwww.'.$service['id'].'.com',
            'ip_address' => fake()->ipv4(),
        ];
    }
}
