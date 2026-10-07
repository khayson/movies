<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\Tmdb;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:generate-notifications {--limit=50 : Max users to process per run}')]
#[Description('Generate personalized notifications: upcoming movies, recommendations, and watchlist updates')]
class GenerateNotifications extends Command
{
    public function handle(Tmdb $tmdb): int
    {
        $this->generateUpcomingNotifications($tmdb);
        $this->generateRecommendations($tmdb);
        $this->generateComingSoonNotifications($tmdb);
        $this->generateWatchlistUpdates($tmdb);

        return self::SUCCESS;
    }

    /**
     * Notify all users about new upcoming movies (broadcast — one per movie, user_id=null).
     */
    private function generateUpcomingNotifications(Tmdb $tmdb): void
    {
        $upcoming = $tmdb->upcoming()['results'] ?? [];
        $count = 0;

        foreach (array_slice($upcoming, 0, 5) as $movie) {
            $title = $movie['title'] ?? 'Unknown';
            $releaseDate = $movie['release_date'] ?? null;
            $tmdbId = $movie['id'] ?? null;

            if (! $tmdbId) {
                continue;
            }

            $exists = UserNotification::where('type', 'upcoming')
                ->whereNull('user_id')
                ->where('tmdb_id', $tmdbId)
                ->exists();

            if ($exists) {
                continue;
            }

            $releaseDateFormatted = $releaseDate ? date('M j, Y', strtotime($releaseDate)) : 'soon';

            UserNotification::create([
                'user_id' => null,
                'type' => 'upcoming',
                'title' => "Upcoming: {$title}",
                'message' => "{$title} is releasing {$releaseDateFormatted}. Add it to your watchlist so you don't miss it!",
                'tmdb_id' => $tmdbId,
                'media_type' => 'movie',
                'poster_path' => $movie['poster_path'] ?? null,
                'link' => "/movies/{$tmdbId}",
            ]);

            $count++;
        }

        $this->info("Created {$count} upcoming movie notifications.");
    }

    /**
     * Notify all users about coming soon movies (now playing that are highly rated).
     */
    private function generateComingSoonNotifications(Tmdb $tmdb): void
    {
        $nowPlaying = $tmdb->nowPlaying()['results'] ?? [];
        $count = 0;

        $highRated = array_filter($nowPlaying, fn (array $movie): bool => ($movie['vote_average'] ?? 0) >= 7.5);

        foreach (array_slice($highRated, 0, 3) as $movie) {
            $title = $movie['title'] ?? 'Unknown';
            $tmdbId = $movie['id'] ?? null;

            if (! $tmdbId) {
                continue;
            }

            $exists = UserNotification::where('type', 'now_playing')
                ->whereNull('user_id')
                ->where('tmdb_id', $tmdbId)
                ->exists();

            if ($exists) {
                continue;
            }

            $rating = number_format($movie['vote_average'] ?? 0, 1);

            UserNotification::create([
                'user_id' => null,
                'type' => 'now_playing',
                'title' => "Now in Theaters: {$title}",
                'message' => "{$title} is now playing with a {$rating}/10 rating. Don't miss it on the big screen!",
                'tmdb_id' => $tmdbId,
                'media_type' => 'movie',
                'poster_path' => $movie['poster_path'] ?? null,
                'link' => "/movies/{$tmdbId}",
            ]);

            $count++;
        }

        $this->info("Created {$count} now playing notifications.");
    }

    /**
     * Generate personalized recommendations per user based on their most-watched genres.
     */
    private function generateRecommendations(Tmdb $tmdb): void
    {
        $limit = max(1, (int) $this->option('limit'));

        $users = User::query()
            ->whereHas('watchHistory')
            ->latest('updated_at')
            ->limit($limit)
            ->cursor();

        $totalCount = 0;

        foreach ($users as $user) {
            $count = $this->generateUserRecommendations($tmdb, $user);
            $totalCount += $count;
        }

        $this->info("Created {$totalCount} personalized recommendation notifications.");
    }

    private function generateUserRecommendations(Tmdb $tmdb, User $user): int
    {
        // Get the user's most-watched TMDB IDs to find genre patterns
        $recentWatches = $user->watchHistory()
            ->latest('updated_at')
            ->limit(20)
            ->get();

        if ($recentWatches->isEmpty()) {
            return 0;
        }

        // Collect genres from the user's watched content via TMDB details
        $genreCounts = [];
        $watchedTmdbIds = $recentWatches->pluck('tmdb_id')->unique()->all();

        foreach ($recentWatches->take(5) as $watch) {
            try {
                $details = $tmdb->details($watch->media_type, $watch->tmdb_id);
                $genres = $details['genres'] ?? [];

                foreach ($genres as $genre) {
                    $genreId = $genre['id'] ?? null;
                    if ($genreId) {
                        $genreCounts[$genreId] = ($genreCounts[$genreId] ?? 0) + 1;
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        if (empty($genreCounts)) {
            return 0;
        }

        // Sort by most-watched genre, pick top genre
        arsort($genreCounts);
        $topGenreId = array_key_first($genreCounts);

        // Discover movies in that genre the user hasn't watched
        $discovered = $tmdb->discoverByGenre('movie', $topGenreId)['results'] ?? [];
        $count = 0;

        foreach (array_slice($discovered, 0, 10) as $movie) {
            $tmdbId = $movie['id'] ?? null;

            if (! $tmdbId || in_array($tmdbId, $watchedTmdbIds, true)) {
                continue;
            }

            // Don't create duplicate recommendation notifications
            $exists = UserNotification::where('type', 'recommendation')
                ->where('user_id', $user->id)
                ->where('tmdb_id', $tmdbId)
                ->exists();

            if ($exists) {
                continue;
            }

            $title = $movie['title'] ?? 'Unknown';
            $rating = number_format($movie['vote_average'] ?? 0, 1);

            UserNotification::create([
                'user_id' => $user->id,
                'type' => 'recommendation',
                'title' => "Recommended: {$title}",
                'message' => "Based on your watch history, we think you'll love {$title} ({$rating}/10). Give it a watch!",
                'tmdb_id' => $tmdbId,
                'media_type' => 'movie',
                'poster_path' => $movie['poster_path'] ?? null,
                'link' => "/movies/{$tmdbId}",
            ]);

            $count++;

            if ($count >= 3) {
                break;
            }
        }

        return $count;
    }

    /**
     * Generate notifications for watchlist items that have updates.
     */
    private function generateWatchlistUpdates(Tmdb $tmdb): void
    {
        $limit = max(1, (int) $this->option('limit'));

        $users = User::query()
            ->whereHas('watchlist')
            ->latest('updated_at')
            ->limit($limit)
            ->cursor();

        $count = 0;

        foreach ($users as $user) {
            $watchlistItems = $user->watchlist()->take(5)->get();

            foreach ($watchlistItems as $item) {
                // Determine if it was recently released or updated. 
                // For demonstration, we just remind them it is on their watchlist.
                $title = $item->title ?? 'Watchlist Item';
                $tmdbId = $item->tmdb_id;
                
                $exists = UserNotification::where('user_id', $user->id)
                    ->where('tmdb_id', $tmdbId)
                    ->where('type', 'watchlist_update')
                    ->exists();

                if ($exists) {
                    continue;
                }

                UserNotification::create([
                    'user_id' => $user->id,
                    'type' => 'watchlist_update',
                    'title' => "Watchlist Reminder: {$title}",
                    'message' => "{$title} is on your watchlist. Have you checked for updates lately?",
                    'tmdb_id' => $tmdbId,
                    'media_type' => $item->media_type ?? 'movie',
                    'poster_path' => $item->poster_path,
                    'link' => "/{$item->media_type}/{$tmdbId}",
                ]);

                $count++;
            }
        }

        $this->info("Created {$count} watchlist update notifications.");
    }
}
