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
     * @var array<int, array{id: int, title: string, poster: string, type: string}>
     */
    private static array $catalog = [
        ['id' => 550, 'title' => 'Fight Club', 'poster' => '/pB8BM7pdSp6B6Ih7QI4S2t0POsFj.jpg', 'type' => 'movie'],
        ['id' => 680, 'title' => 'Pulp Fiction', 'poster' => '/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg', 'type' => 'movie'],
        ['id' => 278, 'title' => 'The Shawshank Redemption', 'poster' => '/9cjIGRlM9DXOM3VaOisSMCWJqNm.jpg', 'type' => 'movie'],
        ['id' => 238, 'title' => 'The Godfather', 'poster' => '/3bhkrj58Vtu7enYsRolD1fZdja1.jpg', 'type' => 'movie'],
        ['id' => 155, 'title' => 'The Dark Knight', 'poster' => '/qJ2tW6WMUDux911r6m7haRef0WH.jpg', 'type' => 'movie'],
        ['id' => 27205, 'title' => 'Inception', 'poster' => '/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg', 'type' => 'movie'],
        ['id' => 157336, 'title' => 'Interstellar', 'poster' => '/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', 'type' => 'movie'],
        ['id' => 496243, 'title' => 'Parasite', 'poster' => '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', 'type' => 'movie'],
        ['id' => 872585, 'title' => 'Oppenheimer', 'poster' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg', 'type' => 'movie'],
        ['id' => 1396, 'title' => 'Breaking Bad', 'poster' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg', 'type' => 'tv'],
        ['id' => 66732, 'title' => 'Stranger Things', 'poster' => '/49WJfeN0moxb9IPfGn8AIqMGskD.jpg', 'type' => 'tv'],
        ['id' => 100088, 'title' => 'The Last of Us', 'poster' => '/uKvVjHNqB5VmOrdxqAt2F7J78ED.jpg', 'type' => 'tv'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $item = fake()->randomElement(self::$catalog);

        return [
            'user_id' => User::factory(),
            'tmdb_id' => $item['id'],
            'media_type' => $item['type'],
            'title' => $item['title'],
            'poster_path' => $item['poster'],
        ];
    }
}
