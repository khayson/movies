<?php

namespace App\Services;

use App\Models\User;

class BadgeService
{
    public function checkAndAward(User $user): void
    {
        $watchCount = $user->watchHistory()->count();
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
    }

    public function awardIf(User $user, string $badgeKey, bool $condition): void
    {
        if (! $condition) {
            return;
        }

        $user->badges()->firstOrCreate(['badge_key' => $badgeKey]);
    }
}
