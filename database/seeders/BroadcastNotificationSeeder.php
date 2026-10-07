<?php

namespace Database\Seeders;

use App\Models\UserNotification;
use Illuminate\Database\Seeder;

class BroadcastNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $broadcasts = [
            [
                'user_id' => null,
                'type' => 'announcement',
                'title' => 'Welcome to StreamVault!',
                'message' => 'Explore thousands of movies, TV shows, anime, and collections. Create a free account to sync your watchlist, track history, and host watch parties.',
                'tmdb_id' => null,
                'media_type' => null,
                'poster_path' => null,
                'link' => '/register',
                'read_at' => null,
                'created_at' => now()->subHours(2),
            ],
            [
                'user_id' => null,
                'type' => 'new_release',
                'title' => 'Dune: Part Two is now streaming',
                'message' => 'Paul Atreides unites with Chani and the Fremen in an epic battle on Arrakis. Watch now in ultra HD.',
                'tmdb_id' => 693134,
                'media_type' => 'movie',
                'poster_path' => '/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg',
                'link' => '/movies/693134',
                'read_at' => null,
                'created_at' => now()->subHours(6),
            ],
            [
                'user_id' => null,
                'type' => 'watch_party',
                'title' => 'Live Watch Parties are Live!',
                'message' => 'Watch shows in sync with friends and chat in real-time rooms without installing anything.',
                'tmdb_id' => 1396,
                'media_type' => 'tv',
                'poster_path' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg',
                'link' => '/watch-parties',
                'read_at' => null,
                'created_at' => now()->subDay(),
            ],
            [
                'user_id' => null,
                'type' => 'trending',
                'title' => 'Weekly Trending Spotlight',
                'message' => 'Discover the top rated and most popular titles trending across our community this week.',
                'tmdb_id' => 496243,
                'media_type' => 'movie',
                'poster_path' => '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg',
                'link' => '/trending',
                'read_at' => null,
                'created_at' => now()->subDays(2),
            ],
        ];

        foreach ($broadcasts as $broadcast) {
            UserNotification::firstOrCreate(
                ['title' => $broadcast['title'], 'user_id' => null],
                $broadcast
            );
        }
    }
}
