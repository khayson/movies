<?php

namespace App\Console\Commands;

use App\Mail\WeeklyDigest;
use App\Models\User;
use App\Services\Tmdb;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-weekly-digest')]
#[Description('Send weekly digest email to all users')]
class SendWeeklyDigest extends Command
{
    public function handle(Tmdb $tmdb): int
    {
        $trending = array_slice($tmdb->trending('all', 'week')['results'] ?? [], 0, 5);
        $weekAgo = now()->subWeek();

        $users = User::whereNotNull('email_verified_at')->cursor();
        $sent = 0;

        foreach ($users as $user) {
            $watchedThisWeek = $user->watchHistory()->where('created_at', '>=', $weekAgo)->count();
            $reviewsThisWeek = $user->reviews()->where('created_at', '>=', $weekAgo)->count();
            $currentStreak = $user->watchHistory()
                ->select('created_at')
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->pluck('created_at')
                ->map(fn ($d) => $d->toDateString())
                ->unique()
                ->values();

            $streak = 0;
            $today = now()->toDateString();
            foreach ($currentStreak as $i => $day) {
                $expected = now()->subDays($i)->toDateString();
                if ($day === $expected) {
                    $streak++;
                } else {
                    break;
                }
            }

            $friendActivity = [];
            $followingIds = $user->following()->pluck('users.id');
            if ($followingIds->isNotEmpty()) {
                $friendActivity = User::whereIn('id', $followingIds)
                    ->withCount(['watchHistory as recent_watches' => fn ($q) => $q->where('created_at', '>=', $weekAgo)])
                    ->having('recent_watches', '>', 0)
                    ->orderByDesc('recent_watches')
                    ->limit(3)
                    ->get()
                    ->map(fn (User $u) => ['name' => $u->name, 'watches' => $u->recent_watches])
                    ->all();
            }

            $digestData = [
                'trending' => array_map(fn (array $item) => [
                    'title' => $item['title'] ?? $item['name'] ?? '',
                    'rating' => number_format($item['vote_average'] ?? 0, 1),
                    'type' => isset($item['first_air_date']) ? 'TV' : 'Movie',
                    'poster' => ! empty($item['poster_path']) ? $tmdb->imageUrl($item['poster_path'], 'w185') : null,
                ], $trending),
                'watched_this_week' => $watchedThisWeek,
                'reviews_this_week' => $reviewsThisWeek,
                'streak' => $streak,
                'friend_activity' => $friendActivity,
            ];

            Mail::to($user)->queue(new WeeklyDigest($user, $digestData));
            $sent++;
        }

        $this->info("Queued weekly digest for {$sent} users.");

        return self::SUCCESS;
    }
}
