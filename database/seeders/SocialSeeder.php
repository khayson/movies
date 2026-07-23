<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Follow;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WatchParty;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class SocialSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('email', [
            'nanaotoo77@gmail.com', 'sarah.mitchell@gmail.com', 'jchen.movies@gmail.com',
            'maria.santos@outlook.com', 'alexthompson@hotmail.com', 'priya.sharma@yahoo.com',
            'dan.okafor@gmail.com', 'emma.larsson@proton.me', 'ryankim92@gmail.com',
            'olivia.rossi@gmail.com', 'tylerb@gmail.com',
        ])->get()->keyBy('email');

        $this->seedFollows($users);
        $this->seedActivities($users);
        $this->seedNotifications($users);
        $this->seedWatchParties($users);
    }

    /**
     * @param  Collection<string, User>  $users
     */
    private function seedFollows(Collection $users): void
    {
        $pairs = [
            ['sarah.mitchell@gmail.com', 'nanaotoo77@gmail.com'],
            ['sarah.mitchell@gmail.com', 'jchen.movies@gmail.com'],
            ['sarah.mitchell@gmail.com', 'maria.santos@outlook.com'],
            ['jchen.movies@gmail.com', 'nanaotoo77@gmail.com'],
            ['jchen.movies@gmail.com', 'sarah.mitchell@gmail.com'],
            ['jchen.movies@gmail.com', 'alexthompson@hotmail.com'],
            ['jchen.movies@gmail.com', 'ryankim92@gmail.com'],
            ['maria.santos@outlook.com', 'sarah.mitchell@gmail.com'],
            ['maria.santos@outlook.com', 'priya.sharma@yahoo.com'],
            ['maria.santos@outlook.com', 'emma.larsson@proton.me'],
            ['alexthompson@hotmail.com', 'nanaotoo77@gmail.com'],
            ['alexthompson@hotmail.com', 'jchen.movies@gmail.com'],
            ['alexthompson@hotmail.com', 'dan.okafor@gmail.com'],
            ['priya.sharma@yahoo.com', 'maria.santos@outlook.com'],
            ['priya.sharma@yahoo.com', 'nanaotoo77@gmail.com'],
            ['priya.sharma@yahoo.com', 'ryankim92@gmail.com'],
            ['dan.okafor@gmail.com', 'jchen.movies@gmail.com'],
            ['dan.okafor@gmail.com', 'alexthompson@hotmail.com'],
            ['emma.larsson@proton.me', 'sarah.mitchell@gmail.com'],
            ['emma.larsson@proton.me', 'maria.santos@outlook.com'],
            ['emma.larsson@proton.me', 'olivia.rossi@gmail.com'],
            ['ryankim92@gmail.com', 'jchen.movies@gmail.com'],
            ['ryankim92@gmail.com', 'priya.sharma@yahoo.com'],
            ['ryankim92@gmail.com', 'nanaotoo77@gmail.com'],
            ['olivia.rossi@gmail.com', 'emma.larsson@proton.me'],
            ['olivia.rossi@gmail.com', 'sarah.mitchell@gmail.com'],
            ['nanaotoo77@gmail.com', 'sarah.mitchell@gmail.com'],
            ['nanaotoo77@gmail.com', 'jchen.movies@gmail.com'],
            ['nanaotoo77@gmail.com', 'alexthompson@hotmail.com'],
            ['nanaotoo77@gmail.com', 'priya.sharma@yahoo.com'],
            ['tylerb@gmail.com', 'nanaotoo77@gmail.com'],
            ['tylerb@gmail.com', 'sarah.mitchell@gmail.com'],
        ];

        foreach ($pairs as [$followerEmail, $followingEmail]) {
            $follower = $users[$followerEmail] ?? null;
            $following = $users[$followingEmail] ?? null;
            if ($follower && $following) {
                Follow::create([
                    'follower_id' => $follower->id,
                    'following_id' => $following->id,
                    'created_at' => fake()->dateTimeBetween('-3 months'),
                ]);
            }
        }
    }

    /**
     * @param  Collection<string, User>  $users
     */
    private function seedActivities(Collection $users): void
    {
        $data = [
            ['sarah.mitchell@gmail.com', 'review', 'wrote a review for', 496243, 'movie', 'Parasite', '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg'],
            ['sarah.mitchell@gmail.com', 'favorite', 'added to favorites', 100088, 'tv', 'The Last of Us', '/uKvVjHNqB5VmOrdxqAt2F7J78ED.jpg'],
            ['jchen.movies@gmail.com', 'review', 'wrote a review for', 550, 'movie', 'Fight Club', '/pB8BM7pdSp6B6Ih7QI4S2t0POsFj.jpg'],
            ['jchen.movies@gmail.com', 'collection', 'created a collection', null, null, 'Nolan Ranked', null],
            ['maria.santos@outlook.com', 'review', 'wrote a review for', 346698, 'movie', 'Barbie', '/iuFNMS8U5cb6xfzi51Dbkovj7vM.jpg'],
            ['maria.santos@outlook.com', 'watchlist', 'added to watchlist', 872585, 'movie', 'Oppenheimer', '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg'],
            ['alexthompson@hotmail.com', 'review', 'wrote a review for', 278, 'movie', 'The Shawshank Redemption', '/9cjIGRlM9DXOM3VaOisSMCWJqNm.jpg'],
            ['priya.sharma@yahoo.com', 'review', 'wrote a review for', 496243, 'movie', 'Parasite', '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg'],
            ['dan.okafor@gmail.com', 'review', 'wrote a review for', 550, 'movie', 'Fight Club', '/pB8BM7pdSp6B6Ih7QI4S2t0POsFj.jpg'],
            ['emma.larsson@proton.me', 'favorite', 'added to favorites', 569094, 'movie', 'Spider-Man: Across the Spider-Verse', '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg'],
            ['emma.larsson@proton.me', 'review', 'wrote a review for', 66732, 'tv', 'Stranger Things', '/49WJfeN0moxb9IPfGn8AIqMGskD.jpg'],
            ['ryankim92@gmail.com', 'review', 'wrote a review for', 496243, 'movie', 'Parasite', '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg'],
            ['ryankim92@gmail.com', 'collection', 'created a collection', null, null, 'Korean Cinema Spotlight', null],
            ['olivia.rossi@gmail.com', 'review', 'wrote a review for', 238, 'movie', 'The Godfather', '/3bhkrj58Vtu7enYsRolD1fZdja1.jpg'],
            ['nanaotoo77@gmail.com', 'review', 'wrote a review for', 155, 'movie', 'The Dark Knight', '/qJ2tW6WMUDux911r6m7haRef0WH.jpg'],
            ['nanaotoo77@gmail.com', 'collection', 'created a collection', null, null, 'Mind-Bending Sci-Fi', null],
            ['nanaotoo77@gmail.com', 'favorite', 'added to favorites', 693134, 'movie', 'Dune: Part Two', '/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg'],
            ['tylerb@gmail.com', 'follow', 'started following Nana Otoo', null, null, null, null],
        ];

        foreach ($data as [$email, $type, $description, $tmdbId, $mediaType, $title, $poster]) {
            $user = $users[$email] ?? null;
            if ($user) {
                Activity::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'description' => $description,
                    'tmdb_id' => $tmdbId,
                    'media_type' => $mediaType,
                    'title' => $title,
                    'poster_path' => $poster,
                    'created_at' => fake()->dateTimeBetween('-2 months'),
                ]);
            }
        }
    }

    /**
     * @param  Collection<string, User>  $users
     */
    private function seedNotifications(Collection $users): void
    {
        $data = [
            ['nanaotoo77@gmail.com', 'follow', 'Sarah Mitchell followed you', 'You have a new follower!', null, null, null, true],
            ['nanaotoo77@gmail.com', 'follow', 'James Chen followed you', 'You have a new follower!', null, null, null, true],
            ['nanaotoo77@gmail.com', 'follow', 'Tyler Brooks followed you', 'You have a new follower!', null, null, null, false],
            ['nanaotoo77@gmail.com', 'activity', 'Sarah Mitchell reviewed Parasite', 'Someone you follow wrote a review', 496243, 'movie', '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', false],
            ['nanaotoo77@gmail.com', 'new_release', 'Dune: Part Two is now streaming', 'Available on multiple platforms', 693134, 'movie', '/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg', false],
            ['sarah.mitchell@gmail.com', 'follow', 'Emma Larsson followed you', 'You have a new follower!', null, null, null, true],
            ['sarah.mitchell@gmail.com', 'new_release', 'The Last of Us Season 2 is coming', 'New season for a show you favorited', 100088, 'tv', '/uKvVjHNqB5VmOrdxqAt2F7J78ED.jpg', false],
            ['jchen.movies@gmail.com', 'follow', 'Daniel Okafor followed you', 'You have a new follower!', null, null, null, true],
            ['jchen.movies@gmail.com', 'activity', 'Ryan Kim reviewed Parasite', 'Someone you follow wrote a review', 496243, 'movie', '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', false],
            ['ryankim92@gmail.com', 'watch_party', 'Breaking Bad Rewatch Club starting soon', 'James Chen invited you to a watch party', 1396, 'tv', '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg', false],
        ];

        foreach ($data as [$email, $type, $title, $message, $tmdbId, $mediaType, $poster, $read]) {
            $user = $users[$email] ?? null;
            if ($user) {
                UserNotification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'tmdb_id' => $tmdbId,
                    'media_type' => $mediaType,
                    'poster_path' => $poster,
                    'read_at' => $read ? fake()->dateTimeBetween('-1 week') : null,
                    'created_at' => fake()->dateTimeBetween('-2 weeks'),
                ]);
            }
        }
    }

    /**
     * @param  Collection<string, User>  $users
     */
    private function seedWatchParties(Collection $users): void
    {
        $owner = $users['nanaotoo77@gmail.com'] ?? null;
        $sarah = $users['sarah.mitchell@gmail.com'] ?? null;
        $james = $users['jchen.movies@gmail.com'] ?? null;

        if ($owner) {
            WatchParty::create([
                'host_id' => $owner->id,
                'title' => 'Friday Night Nolan Marathon',
                'tmdb_id' => 27205,
                'media_type' => 'movie',
                'poster_path' => '/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg',
                'code' => 'NOLAN001',
                'starts_at' => now()->addHours(2),
                'is_active' => true,
            ]);
        }

        if ($sarah) {
            WatchParty::create([
                'host_id' => $sarah->id,
                'title' => 'Oscar Watch Party 2024',
                'tmdb_id' => 872585,
                'media_type' => 'movie',
                'poster_path' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg',
                'code' => 'OSCAR024',
                'starts_at' => now()->subDays(3),
                'is_active' => false,
            ]);
        }

        if ($james) {
            WatchParty::create([
                'host_id' => $james->id,
                'title' => 'Breaking Bad Rewatch Club',
                'tmdb_id' => 1396,
                'media_type' => 'tv',
                'poster_path' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg',
                'code' => 'BBAD2024',
                'starts_at' => now()->addDay(),
                'is_active' => true,
            ]);
        }
    }
}
