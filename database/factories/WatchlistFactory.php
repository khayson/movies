<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Watchlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Watchlist>
 */
class WatchlistFactory extends Factory
{
    /**
     * @var array<int, array{id: int, title: string, poster: string, overview: string, release: string, rating: float, type: string}>
     */
    private static array $catalog = [
        ['id' => 693134, 'title' => 'Dune: Part Two', 'poster' => '/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg', 'overview' => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family.', 'release' => '2024-02-27', 'rating' => 8.2, 'type' => 'movie'],
        ['id' => 346698, 'title' => 'Barbie', 'poster' => '/iuFNMS8U5cb6xfzi51Dbkovj7vM.jpg', 'overview' => 'Barbie and Ken are having the time of their lives in the colorful and seemingly perfect world of Barbie Land.', 'release' => '2023-07-19', 'rating' => 7.0, 'type' => 'movie'],
        ['id' => 872585, 'title' => 'Oppenheimer', 'poster' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg', 'overview' => 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.', 'release' => '2023-07-19', 'rating' => 8.1, 'type' => 'movie'],
        ['id' => 438631, 'title' => 'Dune', 'poster' => '/d5NXSklXo0qyIYkgV94XAgMIckC.jpg', 'overview' => 'Paul Atreides must travel to the most dangerous planet in the universe to ensure the future of his family and his people.', 'release' => '2021-09-15', 'rating' => 7.8, 'type' => 'movie'],
        ['id' => 496243, 'title' => 'Parasite', 'poster' => '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', 'overview' => 'All unemployed, Ki-taek and his family take a peculiar interest in the wealthy and glamorous Parks.', 'release' => '2019-05-30', 'rating' => 8.5, 'type' => 'movie'],
        ['id' => 94997, 'title' => 'House of the Dragon', 'poster' => '/z2yahl2uefxDCl0nogcRBstwruJ.jpg', 'overview' => 'The Targaryen dynasty is at the absolute apex of its power, with more than 15 dragons under their yoke.', 'release' => '2022-08-21', 'rating' => 8.4, 'type' => 'tv'],
        ['id' => 100088, 'title' => 'The Last of Us', 'poster' => '/uKvVjHNqB5VmOrdxqAt2F7J78ED.jpg', 'overview' => 'Joel and Ellie must survive in a post-apocalyptic world overrun by the infected.', 'release' => '2023-01-15', 'rating' => 8.6, 'type' => 'tv'],
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
            'overview' => $item['overview'],
            'release_date' => $item['release'],
            'vote_average' => $item['rating'],
        ];
    }
}
