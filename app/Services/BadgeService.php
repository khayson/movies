<?php

namespace App\Services;

use App\Models\User;

class BadgeService
{
    public function checkAndAward(User $user): void
    {
        $watchCount = $user->watchHistory()->visible()->count();
        $reviewCount = $user->reviews()->count();
        $collectionCount = $user->collections()->count();
        $favoriteCount = $user->favorites()->count();
        $collectionItemCount = $user->collections()->withCount('items')->get()->sum('items_count');

        $this->awardIf($user, 'first_watch', $watchCount >= 1);
        $this->awardIf($user, 'binge_watcher', $watchCount >= 10);
        $this->awardIf($user, 'movie_buff', $watchCount >= 50);
        $this->awardIf($user, 'cinephile', $watchCount >= 100);
        $this->awardIf($user, 'first_review', $reviewCount >= 1);
        $this->awardIf($user, 'prolific_reviewer', $reviewCount >= 10);
        $this->awardIf($user, 'collector', $collectionCount >= 1);
        $this->awardIf($user, 'curator', $collectionItemCount >= 25);
        $this->awardIf($user, 'favoriter', $favoriteCount >= 10);

        $this->awardIf($user, 'early_adopter', $user->created_at?->lt(now()->subMonths(6)) ?? false);

        $hasNightWatch = $user->watchHistory()
            ->visible()
            ->whereTime('created_at', '<', '04:00:00')
            ->exists();
        $this->awardIf($user, 'night_owl', $hasNightWatch);

        $completedSeason = $user->episodeWatches()
            ->select(['tmdb_id', 'season_number'])
            ->groupBy('tmdb_id', 'season_number')
            ->havingRaw('COUNT(*) >= 8')
            ->exists();
        $this->awardIf($user, 'season_finisher', $completedSeason);
    }

    public function awardIf(User $user, string $badgeKey, bool $condition): void
    {
        if (! $condition) {
            return;
        }

        $user->badges()->firstOrCreate(['badge_key' => $badgeKey]);
    }
}
