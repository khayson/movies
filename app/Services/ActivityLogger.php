<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Models\UserNotification;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        User $user,
        string $type,
        string $description,
        ?int $tmdbId = null,
        ?string $mediaType = null,
        ?string $title = null,
        ?string $posterPath = null,
        ?array $metadata = null,
    ): Activity {
        return Activity::create([
            'user_id' => $user->id,
            'type' => $type,
            'description' => $description,
            'tmdb_id' => $tmdbId,
            'media_type' => $mediaType,
            'title' => $title,
            'poster_path' => $posterPath,
            'metadata' => $metadata,
        ]);
    }

    public function notifyFollowers(User $user, Activity $activity): void
    {
        $followers = $user->followers()->get();

        foreach ($followers as $follower) {
            if ($follower->preferences['email_notifications'] ?? true) {
                UserNotification::create([
                    'user_id' => $follower->id,
                    'type' => 'activity',
                    'title' => "{$user->name} {$activity->description}",
                    'message' => $activity->title ? "Related to: {$activity->title}" : $activity->description,
                    'tmdb_id' => $activity->tmdb_id,
                    'media_type' => $activity->media_type,
                    'poster_path' => $activity->poster_path,
                ]);
            }
        }
    }
}
