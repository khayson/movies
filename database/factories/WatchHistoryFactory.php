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
     * @var array<int, array{id: int, title: string, poster: string, type: string}>
     */
    private static array $catalog = [
        ['id' => 550, 'title' => 'Fight Club', 'poster' => '/pB8BM7pdSp6B6Ih7QI4S2t0POsFj.jpg', 'type' => 'movie'],
        ['id' => 680, 'title' => 'Pulp Fiction', 'poster' => '/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg', 'type' => 'movie'],
        ['id' => 278, 'title' => 'The Shawshank Redemption', 'poster' => '/9cjIGRlM9DXOM3VaOisSMCWJqNm.jpg', 'type' => 'movie'],
        ['id' => 155, 'title' => 'The Dark Knight', 'poster' => '/qJ2tW6WMUDux911r6m7haRef0WH.jpg', 'type' => 'movie'],
        ['id' => 27205, 'title' => 'Inception', 'poster' => '/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg', 'type' => 'movie'],
        ['id' => 157336, 'title' => 'Interstellar', 'poster' => '/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', 'type' => 'movie'],
        ['id' => 603, 'title' => 'The Matrix', 'poster' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg', 'type' => 'movie'],
        ['id' => 569094, 'title' => 'Spider-Man: Across the Spider-Verse', 'poster' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg', 'type' => 'movie'],
        ['id' => 872585, 'title' => 'Oppenheimer', 'poster' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg', 'type' => 'movie'],
        ['id' => 496243, 'title' => 'Parasite', 'poster' => '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', 'type' => 'movie'],
        ['id' => 1396, 'title' => 'Breaking Bad', 'poster' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg', 'type' => 'tv'],
        ['id' => 66732, 'title' => 'Stranger Things', 'poster' => '/49WJfeN0moxb9IPfGn8AIqMGskD.jpg', 'type' => 'tv'],
        ['id' => 100088, 'title' => 'The Last of Us', 'poster' => '/uKvVjHNqB5VmOrdxqAt2F7J78ED.jpg', 'type' => 'tv'],
        ['id' => 93405, 'title' => 'Squid Game', 'poster' => '/dDlEmu3EZ0Pgg93K2SVNLCjCSvE.jpg', 'type' => 'tv'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = fake()->randomElement(self::$catalog);
        $isMovie = $item['type'] === 'movie';
        $duration = $isMovie ? fake()->numberBetween(5400, 10800) : fake()->numberBetween(2400, 3600);
        $fullyWatched = fake()->boolean(75);

        return [
            'user_id' => User::factory(),
            'tmdb_id' => $item['id'],
            'media_type' => $item['type'],
            'title' => $item['title'],
            'poster_path' => $item['poster'],
            'progress_seconds' => $fullyWatched ? $duration : fake()->numberBetween((int) ($duration * 0.1), (int) ($duration * 0.8)),
            'duration_seconds' => $duration,
            'season' => $isMovie ? null : fake()->numberBetween(1, 4),
            'episode' => $isMovie ? null : fake()->numberBetween(1, 10),
        ];
    }
}
