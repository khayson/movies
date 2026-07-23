<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WatchParty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WatchParty>
 */
class WatchPartyFactory extends Factory
{
    /**
     * @var array<int, array{name: string, tmdb_id: int, type: string, poster: string}>
     */
    private static array $parties = [
        ['name' => 'Friday Night Nolan Marathon', 'tmdb_id' => 27205, 'type' => 'movie', 'poster' => '/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg'],
        ['name' => 'Breaking Bad Rewatch Club', 'tmdb_id' => 1396, 'type' => 'tv', 'poster' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg'],
        ['name' => 'Oscar Watch Party', 'tmdb_id' => 872585, 'type' => 'movie', 'poster' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg'],
        ['name' => 'Stranger Things Season Premiere', 'tmdb_id' => 66732, 'type' => 'tv', 'poster' => '/49WJfeN0moxb9IPfGn8AIqMGskD.jpg'],
        ['name' => 'Sci-Fi Double Feature', 'tmdb_id' => 157336, 'type' => 'movie', 'poster' => '/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $party = fake()->randomElement(self::$parties);

        return [
            'host_id' => User::factory(),
            'title' => $party['name'],
            'tmdb_id' => $party['tmdb_id'],
            'media_type' => $party['type'],
            'poster_path' => $party['poster'],
            'code' => strtoupper(Str::random(8)),
            'starts_at' => now()->addHours(fake()->numberBetween(1, 48)),
            'is_active' => true,
        ];
    }
}
