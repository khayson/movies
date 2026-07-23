<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @var array<int, array{type: string, description: string, title: string|null, tmdb_id: int|null, media_type: string|null, poster: string|null}>
     */
    private static array $activities = [
        ['type' => 'review', 'description' => 'wrote a review for', 'title' => 'The Dark Knight', 'tmdb_id' => 155, 'media_type' => 'movie', 'poster' => '/qJ2tW6WMUDux911r6m7haRef0WH.jpg'],
        ['type' => 'review', 'description' => 'wrote a review for', 'title' => 'Parasite', 'tmdb_id' => 496243, 'media_type' => 'movie', 'poster' => '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg'],
        ['type' => 'favorite', 'description' => 'added to favorites', 'title' => 'Breaking Bad', 'tmdb_id' => 1396, 'media_type' => 'tv', 'poster' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg'],
        ['type' => 'favorite', 'description' => 'added to favorites', 'title' => 'Inception', 'tmdb_id' => 27205, 'media_type' => 'movie', 'poster' => '/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg'],
        ['type' => 'watchlist', 'description' => 'added to watchlist', 'title' => 'Oppenheimer', 'tmdb_id' => 872585, 'media_type' => 'movie', 'poster' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg'],
        ['type' => 'collection', 'description' => 'created a collection', 'title' => 'Mind-Bending Sci-Fi', 'tmdb_id' => null, 'media_type' => null, 'poster' => null],
        ['type' => 'follow', 'description' => 'started following someone', 'title' => null, 'tmdb_id' => null, 'media_type' => null, 'poster' => null],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activity = fake()->randomElement(self::$activities);

        return [
            'user_id' => User::factory(),
            'type' => $activity['type'],
            'description' => $activity['description'],
            'tmdb_id' => $activity['tmdb_id'],
            'media_type' => $activity['media_type'],
            'title' => $activity['title'],
            'poster_path' => $activity['poster'],
            'metadata' => null,
        ];
    }
}
