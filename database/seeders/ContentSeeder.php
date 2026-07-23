<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\EpisodeWatch;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\WatchHistory;
use App\Models\Watchlist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    /**
     * @var array<int, array{id: int, title: string, poster: string, type: string}>
     */
    private array $catalog = [
        ['id' => 550, 'title' => 'Fight Club', 'poster' => '/pB8BM7pdSp6B6Ih7QI4S2t0POsFj.jpg', 'type' => 'movie'],
        ['id' => 680, 'title' => 'Pulp Fiction', 'poster' => '/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg', 'type' => 'movie'],
        ['id' => 278, 'title' => 'The Shawshank Redemption', 'poster' => '/9cjIGRlM9DXOM3VaOisSMCWJqNm.jpg', 'type' => 'movie'],
        ['id' => 238, 'title' => 'The Godfather', 'poster' => '/3bhkrj58Vtu7enYsRolD1fZdja1.jpg', 'type' => 'movie'],
        ['id' => 155, 'title' => 'The Dark Knight', 'poster' => '/qJ2tW6WMUDux911r6m7haRef0WH.jpg', 'type' => 'movie'],
        ['id' => 569094, 'title' => 'Spider-Man: Across the Spider-Verse', 'poster' => '/8Vt6mWEReuy4Of61Lnj5Xj704m8.jpg', 'type' => 'movie'],
        ['id' => 872585, 'title' => 'Oppenheimer', 'poster' => '/8Gxv8gSFCU0XGDykEGv7zR1n2ua.jpg', 'type' => 'movie'],
        ['id' => 346698, 'title' => 'Barbie', 'poster' => '/iuFNMS8U5cb6xfzi51Dbkovj7vM.jpg', 'type' => 'movie'],
        ['id' => 496243, 'title' => 'Parasite', 'poster' => '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg', 'type' => 'movie'],
        ['id' => 27205, 'title' => 'Inception', 'poster' => '/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg', 'type' => 'movie'],
        ['id' => 120, 'title' => 'The Lord of the Rings: The Fellowship of the Ring', 'poster' => '/6oom5QYQ2yQTMJIbnvbkBL9cHo6.jpg', 'type' => 'movie'],
        ['id' => 603, 'title' => 'The Matrix', 'poster' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg', 'type' => 'movie'],
        ['id' => 157336, 'title' => 'Interstellar', 'poster' => '/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', 'type' => 'movie'],
        ['id' => 122, 'title' => 'The Lord of the Rings: The Return of the King', 'poster' => '/rCzpDGLbOoPwLjy3OAm5NUPOTrC.jpg', 'type' => 'movie'],
        ['id' => 11, 'title' => 'Star Wars', 'poster' => '/6FfCtAuVAW8XJjZ7eWeLibRLWTw.jpg', 'type' => 'movie'],
        ['id' => 862, 'title' => 'Toy Story', 'poster' => '/uXDfjJbdP4ijW5hWSBrPrlKpxab.jpg', 'type' => 'movie'],
        ['id' => 13, 'title' => 'Forrest Gump', 'poster' => '/arw2vcBveWOVZr6pxd9XTd1TdQa.jpg', 'type' => 'movie'],
        ['id' => 550988, 'title' => 'Free Guy', 'poster' => '/xmbU4JTUm8rsdtn7Y3Fcm30GpeT.jpg', 'type' => 'movie'],
        ['id' => 438631, 'title' => 'Dune', 'poster' => '/d5NXSklXo0qyIYkgV94XAgMIckC.jpg', 'type' => 'movie'],
        ['id' => 693134, 'title' => 'Dune: Part Two', 'poster' => '/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg', 'type' => 'movie'],
        ['id' => 1396, 'title' => 'Breaking Bad', 'poster' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg', 'type' => 'tv'],
        ['id' => 1399, 'title' => 'Game of Thrones', 'poster' => '/1XS1oqL89opfnV0O6EREjnXQ5VQ.jpg', 'type' => 'tv'],
        ['id' => 66732, 'title' => 'Stranger Things', 'poster' => '/49WJfeN0moxb9IPfGn8AIqMGskD.jpg', 'type' => 'tv'],
        ['id' => 94997, 'title' => 'House of the Dragon', 'poster' => '/z2yahl2uefxDCl0nogcRBstwruJ.jpg', 'type' => 'tv'],
        ['id' => 100088, 'title' => 'The Last of Us', 'poster' => '/uKvVjHNqB5VmOrdxqAt2F7J78ED.jpg', 'type' => 'tv'],
        ['id' => 63174, 'title' => 'Lucifer', 'poster' => '/ekZobS8isE6mA53RAiGDG93hBxL.jpg', 'type' => 'tv'],
        ['id' => 84958, 'title' => 'Loki', 'poster' => '/voHUmluYmKyleFkTu3lOXQG702u.jpg', 'type' => 'tv'],
        ['id' => 1402, 'title' => 'The Walking Dead', 'poster' => '/xf9wuDcqlUPWABZNeDKPbZUjWx0.jpg', 'type' => 'tv'],
        ['id' => 93405, 'title' => 'Squid Game', 'poster' => '/dDlEmu3EZ0Pgg93K2SVNLCjCSvE.jpg', 'type' => 'tv'],
    ];

    /**
     * @var array<int, array{title: string, body: string, rating: int}>
     */
    private array $reviews = [
        ['title' => 'A masterpiece of modern cinema', 'body' => 'Every frame is meticulously crafted. The performances are raw and authentic, and the storytelling never lets up. This is the kind of film that stays with you for days.', 'rating' => 9],
        ['title' => 'Good but overhyped', 'body' => 'Solid film with great production values, but after years of hype I expected something more groundbreaking. The pacing drags in the second act.', 'rating' => 7],
        ['title' => 'Changed my perspective on the genre', 'body' => 'I normally avoid this type of movie, but a friend insisted. It completely subverts expectations and delivers something genuinely original.', 'rating' => 8],
        ['title' => 'Visually stunning, emotionally flat', 'body' => 'The cinematography is jaw-dropping but the emotional core feels hollow — I never connected with any of the characters. Beautiful to look at, forgettable once it is over.', 'rating' => 6],
        ['title' => 'My new all-time favorite', 'body' => 'I have watched this three times now and notice something new each time. The layered storytelling, the subtle foreshadowing, the brilliant soundtrack — everything works in harmony.', 'rating' => 10],
        ['title' => 'Decent popcorn entertainment', 'body' => 'Not every movie needs to be a deep philosophical experience. This one knows exactly what it is — fun, flashy, and entertaining. Perfect for a Friday night.', 'rating' => 7],
        ['title' => 'Absolutely gripping from start to finish', 'body' => 'I could not look away. The tension is relentless, the performances are powerhouse-level, and the plot twists actually make sense within the story.', 'rating' => 9],
        ['title' => 'The ending ruined it for me', 'body' => 'I was completely hooked for the first two hours. The tension was perfect, the cast delivered. But that final act felt like they ran out of ideas. So close to being perfect.', 'rating' => 5],
        ['title' => 'Perfect date night movie', 'body' => 'Watched this with my partner and we both loved it. Right balance of action, humor, and heart. Not too heavy, not too shallow.', 'rating' => 8],
        ['title' => 'Way too long', 'body' => 'There is a great 90-minute movie buried inside this 3-hour epic. The director clearly fell in love with their own footage. By the third act I was checking my phone.', 'rating' => 4],
    ];

    public function run(): void
    {
        $users = User::whereIn('email', [
            'nanaotoo77@gmail.com', 'sarah.mitchell@gmail.com', 'jchen.movies@gmail.com',
            'maria.santos@outlook.com', 'alexthompson@hotmail.com', 'priya.sharma@yahoo.com',
            'dan.okafor@gmail.com', 'emma.larsson@proton.me', 'ryankim92@gmail.com',
            'olivia.rossi@gmail.com', 'tylerb@gmail.com',
        ])->get()->keyBy('email');

        $this->seedWatchHistory($users);
        $this->seedFavorites($users);
        $this->seedWatchlists($users);
        $this->seedReviews($users);
        $this->seedCollections($users);
        $this->seedEpisodeWatches($users);
        $this->seedBadges($users);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<string, User>  $users
     */
    private function seedWatchHistory(\Illuminate\Database\Eloquent\Collection $users): void
    {
        $profiles = [
            ['nanaotoo77@gmail.com', 20], ['sarah.mitchell@gmail.com', 25],
            ['jchen.movies@gmail.com', 29], ['maria.santos@outlook.com', 12],
            ['alexthompson@hotmail.com', 18], ['priya.sharma@yahoo.com', 14],
            ['dan.okafor@gmail.com', 10], ['emma.larsson@proton.me', 15],
            ['ryankim92@gmail.com', 22], ['olivia.rossi@gmail.com', 10],
            ['tylerb@gmail.com', 3],
        ];

        foreach ($profiles as [$email, $count]) {
            $user = $users[$email] ?? null;
            if (! $user) {
                continue;
            }

            $items = collect($this->catalog)->shuffle()->take($count);
            foreach ($items as $item) {
                $isMovie = $item['type'] === 'movie';
                $duration = $isMovie ? fake()->numberBetween(5400, 10800) : fake()->numberBetween(2400, 3600);
                $done = fake()->boolean(75);

                WatchHistory::create([
                    'user_id' => $user->id,
                    'tmdb_id' => $item['id'],
                    'media_type' => $item['type'],
                    'title' => $item['title'],
                    'poster_path' => $item['poster'],
                    'progress_seconds' => $done ? $duration : fake()->numberBetween((int) ($duration * 0.1), (int) ($duration * 0.8)),
                    'duration_seconds' => $duration,
                    'season' => $isMovie ? null : fake()->numberBetween(1, 3),
                    'episode' => $isMovie ? null : fake()->numberBetween(1, 10),
                    'created_at' => fake()->dateTimeBetween('-6 months'),
                    'updated_at' => fake()->dateTimeBetween('-3 months'),
                ]);
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<string, User>  $users
     */
    private function seedFavorites(\Illuminate\Database\Eloquent\Collection $users): void
    {
        $mapping = [
            ['nanaotoo77@gmail.com', [278, 155, 27205, 157336, 693134, 1396, 100088, 569094, 872585, 438631, 496243, 603]],
            ['sarah.mitchell@gmail.com', [496243, 346698, 680, 278, 13, 862, 66732, 100088, 1399, 84958]],
            ['jchen.movies@gmail.com', [550, 680, 155, 27205, 603, 157336, 438631, 693134, 1396, 93405, 11, 120, 122]],
            ['maria.santos@outlook.com', [346698, 569094, 862, 550988, 66732, 84958, 93405]],
            ['alexthompson@hotmail.com', [278, 238, 155, 120, 122, 11, 13, 680, 1396, 1399]],
            ['priya.sharma@yahoo.com', [496243, 872585, 438631, 693134, 100088, 93405, 63174]],
            ['dan.okafor@gmail.com', [550, 603, 155, 1396, 1402]],
            ['emma.larsson@proton.me', [569094, 346698, 862, 66732, 84958, 100088]],
            ['ryankim92@gmail.com', [496243, 93405, 872585, 1396, 603, 27205, 438631, 693134]],
            ['olivia.rossi@gmail.com', [238, 13, 278, 346698, 100088]],
            ['tylerb@gmail.com', [155]],
        ];

        $catalog = collect($this->catalog);

        foreach ($mapping as [$email, $ids]) {
            $user = $users[$email] ?? null;
            if (! $user) {
                continue;
            }

            foreach ($ids as $tmdbId) {
                $item = $catalog->firstWhere('id', $tmdbId);
                if ($item) {
                    Favorite::create([
                        'user_id' => $user->id,
                        'tmdb_id' => $item['id'],
                        'media_type' => $item['type'],
                        'title' => $item['title'],
                        'poster_path' => $item['poster'],
                        'created_at' => fake()->dateTimeBetween('-6 months'),
                    ]);
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<string, User>  $users
     */
    private function seedWatchlists(\Illuminate\Database\Eloquent\Collection $users): void
    {
        $overviews = [
            693134 => 'Paul Atreides unites with Chani and the Fremen while seeking revenge.',
            346698 => 'Barbie and Ken have the time of their lives in the colorful world of Barbie Land.',
            872585 => 'The story of J. Robert Oppenheimer and his role in the development of the atomic bomb.',
            550988 => 'A bank teller discovers he is a background player in an open-world video game.',
            438631 => 'Paul Atreides must travel to the most dangerous planet in the universe.',
        ];

        $mapping = [
            ['nanaotoo77@gmail.com', [346698, 550988]],
            ['sarah.mitchell@gmail.com', [438631, 693134, 872585]],
            ['maria.santos@outlook.com', [278, 238, 680, 872585, 157336]],
            ['alexthompson@hotmail.com', [496243, 93405, 100088]],
            ['priya.sharma@yahoo.com', [550, 155, 120, 13]],
            ['dan.okafor@gmail.com', [569094, 346698, 872585, 438631, 693134]],
            ['emma.larsson@proton.me', [238, 680, 550, 603, 1396]],
            ['ryankim92@gmail.com', [550988, 346698, 862]],
            ['olivia.rossi@gmail.com', [603, 27205, 66732, 84958, 1396, 1402]],
        ];

        $catalog = collect($this->catalog);

        foreach ($mapping as [$email, $ids]) {
            $user = $users[$email] ?? null;
            if (! $user) {
                continue;
            }

            foreach ($ids as $tmdbId) {
                $item = $catalog->firstWhere('id', $tmdbId);
                if ($item) {
                    Watchlist::create([
                        'user_id' => $user->id,
                        'tmdb_id' => $item['id'],
                        'media_type' => $item['type'],
                        'title' => $item['title'],
                        'poster_path' => $item['poster'],
                        'overview' => $overviews[$tmdbId] ?? 'A critically acclaimed title worth watching.',
                        'release_date' => fake()->date(),
                        'vote_average' => fake()->randomFloat(1, 7, 9),
                        'created_at' => fake()->dateTimeBetween('-3 months'),
                    ]);
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<string, User>  $users
     */
    private function seedReviews(\Illuminate\Database\Eloquent\Collection $users): void
    {
        $mapping = [
            ['nanaotoo77@gmail.com', [[278, 0], [155, 4], [27205, 6], [1396, 0], [872585, 2], [693134, 8]]],
            ['sarah.mitchell@gmail.com', [[496243, 4], [346698, 5], [680, 0], [278, 6], [66732, 2], [100088, 0], [862, 8], [13, 4], [1399, 7], [93405, 1]]],
            ['jchen.movies@gmail.com', [[550, 0], [680, 4], [155, 6], [603, 0], [27205, 2], [157336, 6], [438631, 3], [693134, 6], [11, 4], [120, 0], [122, 0], [1396, 4], [93405, 5], [872585, 9]]],
            ['maria.santos@outlook.com', [[346698, 8], [569094, 2], [66732, 6], [93405, 1]]],
            ['alexthompson@hotmail.com', [[278, 4], [238, 0], [155, 0], [120, 4], [11, 2], [680, 6], [1396, 0], [1399, 7]]],
            ['priya.sharma@yahoo.com', [[496243, 0], [872585, 6], [93405, 2], [63174, 5], [100088, 6]]],
            ['dan.okafor@gmail.com', [[550, 6], [603, 2], [1396, 4]]],
            ['emma.larsson@proton.me', [[569094, 4], [346698, 5], [66732, 0], [84958, 1], [100088, 6]]],
            ['ryankim92@gmail.com', [[496243, 0], [93405, 3], [1396, 4], [603, 0], [872585, 2], [438631, 3], [693134, 6]]],
            ['olivia.rossi@gmail.com', [[238, 0], [13, 4], [346698, 8], [100088, 2]]],
        ];

        $catalog = collect($this->catalog);

        foreach ($mapping as [$email, $entries]) {
            $user = $users[$email] ?? null;
            if (! $user) {
                continue;
            }

            foreach ($entries as [$tmdbId, $reviewIdx]) {
                $item = $catalog->firstWhere('id', $tmdbId);
                $template = $this->reviews[$reviewIdx] ?? $this->reviews[0];

                if ($item) {
                    Review::create([
                        'user_id' => $user->id,
                        'tmdb_id' => $item['id'],
                        'media_type' => $item['type'],
                        'title' => $template['title'],
                        'rating' => $template['rating'],
                        'body' => $template['body'],
                        'contains_spoilers' => $reviewIdx === 7,
                        'helpful_count' => fake()->numberBetween(0, 35),
                        'created_at' => fake()->dateTimeBetween('-4 months'),
                    ]);
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<string, User>  $users
     */
    private function seedCollections(\Illuminate\Database\Eloquent\Collection $users): void
    {
        $catalog = collect($this->catalog);

        $data = [
            ['nanaotoo77@gmail.com', 'Mind-Bending Sci-Fi', 'Films that make you question reality', true, [27205, 157336, 603, 438631, 693134]],
            ['nanaotoo77@gmail.com', 'Weekend Comfort Watches', 'Perfect for lazy Sundays', true, [13, 862, 550988, 346698]],
            ['sarah.mitchell@gmail.com', 'Award Winners That Deserve It', 'Oscar darlings that lived up to the hype', true, [496243, 278, 872585, 238]],
            ['sarah.mitchell@gmail.com', 'Shows I Binged in One Weekend', 'No regrets', true, [66732, 100088, 93405]],
            ['jchen.movies@gmail.com', 'The Perfect Trilogy', 'Lord of the Rings and nothing else compares', true, [120, 122]],
            ['jchen.movies@gmail.com', 'Nolan Ranked', 'Every Christopher Nolan film ranked', true, [155, 27205, 157336, 872585]],
            ['alexthompson@hotmail.com', 'Classic Cinema Essentials', 'Films every cinephile must see', true, [238, 278, 680, 11, 13]],
            ['maria.santos@outlook.com', 'Feel-Good Movie Night', 'For when you need a pick-me-up', true, [346698, 862, 550988, 569094]],
            ['priya.sharma@yahoo.com', 'International Gems', 'Best non-English language films', true, [496243, 93405]],
            ['emma.larsson@proton.me', 'Animated Masterpieces', 'Animation is cinema', true, [862, 569094]],
            ['ryankim92@gmail.com', 'Korean Cinema Spotlight', 'The best of Korean film and TV', true, [496243, 93405]],
            ['dan.okafor@gmail.com', 'Gritty and Raw', 'Unflinching films that don\'t hold back', true, [550, 1396, 603]],
            ['olivia.rossi@gmail.com', 'Italian Sunday Classics', 'What I grew up watching with my nonna', true, [238, 13]],
        ];

        foreach ($data as [$email, $name, $description, $isPublic, $tmdbIds]) {
            $user = $users[$email] ?? null;
            if (! $user) {
                continue;
            }

            $collection = Collection::create([
                'user_id' => $user->id,
                'name' => $name,
                'description' => $description,
                'is_public' => $isPublic,
                'slug' => Str::slug($name).'-'.fake()->unique()->randomNumber(5),
                'created_at' => fake()->dateTimeBetween('-3 months'),
            ]);

            foreach ($tmdbIds as $order => $tmdbId) {
                $item = $catalog->firstWhere('id', $tmdbId);
                if ($item) {
                    CollectionItem::create([
                        'collection_id' => $collection->id,
                        'tmdb_id' => $item['id'],
                        'media_type' => $item['type'],
                        'title' => $item['title'],
                        'poster_path' => $item['poster'],
                        'sort_order' => $order,
                    ]);
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<string, User>  $users
     */
    private function seedEpisodeWatches(\Illuminate\Database\Eloquent\Collection $users): void
    {
        $data = [
            ['jchen.movies@gmail.com', 1396, 5, 13],
            ['sarah.mitchell@gmail.com', 66732, 4, 9],
            ['ryankim92@gmail.com', 93405, 2, 9],
            ['nanaotoo77@gmail.com', 1396, 5, 13],
            ['nanaotoo77@gmail.com', 100088, 1, 9],
            ['priya.sharma@yahoo.com', 100088, 1, 9],
            ['emma.larsson@proton.me', 66732, 3, 8],
            ['alexthompson@hotmail.com', 1396, 3, 13],
            ['maria.santos@outlook.com', 93405, 1, 9],
        ];

        foreach ($data as [$email, $showId, $seasons, $epsPerSeason]) {
            $user = $users[$email] ?? null;
            if (! $user) {
                continue;
            }

            for ($s = 1; $s <= $seasons; $s++) {
                $eps = ($s === $seasons) ? fake()->numberBetween((int) ($epsPerSeason * 0.5), $epsPerSeason) : $epsPerSeason;
                for ($e = 1; $e <= $eps; $e++) {
                    EpisodeWatch::create([
                        'user_id' => $user->id,
                        'tmdb_id' => $showId,
                        'season_number' => $s,
                        'episode_number' => $e,
                        'watched_at' => fake()->dateTimeBetween('-6 months'),
                    ]);
                }
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<string, User>  $users
     */
    private function seedBadges(\Illuminate\Database\Eloquent\Collection $users): void
    {
        $data = [
            ['nanaotoo77@gmail.com', ['first_watch', 'binge_watcher', 'first_review', 'prolific_reviewer', 'collector', 'favoriter', 'early_adopter', 'night_owl']],
            ['sarah.mitchell@gmail.com', ['first_watch', 'binge_watcher', 'first_review', 'prolific_reviewer', 'collector', 'favoriter', 'early_adopter']],
            ['jchen.movies@gmail.com', ['first_watch', 'binge_watcher', 'first_review', 'prolific_reviewer', 'collector', 'curator', 'favoriter', 'season_finisher', 'night_owl']],
            ['maria.santos@outlook.com', ['first_watch', 'binge_watcher', 'first_review', 'collector']],
            ['alexthompson@hotmail.com', ['first_watch', 'binge_watcher', 'first_review', 'collector', 'favoriter']],
            ['priya.sharma@yahoo.com', ['first_watch', 'binge_watcher', 'first_review', 'collector', 'season_finisher']],
            ['dan.okafor@gmail.com', ['first_watch', 'binge_watcher', 'first_review', 'collector']],
            ['emma.larsson@proton.me', ['first_watch', 'binge_watcher', 'first_review', 'collector']],
            ['ryankim92@gmail.com', ['first_watch', 'binge_watcher', 'first_review', 'collector', 'season_finisher', 'night_owl']],
            ['olivia.rossi@gmail.com', ['first_watch', 'binge_watcher', 'first_review', 'collector']],
            ['tylerb@gmail.com', ['first_watch']],
        ];

        foreach ($data as [$email, $keys]) {
            $user = $users[$email] ?? null;
            if (! $user) {
                continue;
            }

            foreach ($keys as $key) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_key' => $key,
                    'earned_at' => fake()->dateTimeBetween('-5 months'),
                ]);
            }
        }
    }
}
