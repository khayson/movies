<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class StreakService
{
    /**
     * @return array{current: int, longest: int, active_days: array<string>}
     */
    public function calculate(User $user, int $lookbackDays = 90): array
    {
        $activeDays = $user->watchHistory()
            ->where('updated_at', '>=', now()->subDays($lookbackDays))
            ->selectRaw('DATE(updated_at) as watch_date')
            ->groupBy('watch_date')
            ->orderBy('watch_date')
            ->pluck('watch_date')
            ->map(fn (string $date) => Carbon::parse($date)->format('Y-m-d'))
            ->values()
            ->toArray();

        $currentStreak = 0;
        $longestStreak = 0;
        $streak = 0;
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');

        for ($i = count($activeDays) - 1; $i >= 0; $i--) {
            $day = $activeDays[$i];
            $prevDay = $i > 0 ? $activeDays[$i - 1] : null;

            if ($i === count($activeDays) - 1) {
                if ($day === $today || $day === $yesterday) {
                    $currentStreak = 1;
                }
            }
        }

        $streak = 1;
        for ($i = count($activeDays) - 1; $i > 0; $i--) {
            $current = Carbon::parse($activeDays[$i]);
            $previous = Carbon::parse($activeDays[$i - 1]);

            if ($current->diffInDays($previous) === 1) {
                $streak++;
            } else {
                $longestStreak = max($longestStreak, $streak);
                $streak = 1;
            }
        }
        $longestStreak = max($longestStreak, $streak);

        $currentStreak = 0;
        if (count($activeDays) > 0) {
            $lastDay = end($activeDays);
            if ($lastDay === $today || $lastDay === $yesterday) {
                $currentStreak = 1;
                for ($i = count($activeDays) - 2; $i >= 0; $i--) {
                    $current = Carbon::parse($activeDays[$i + 1]);
                    $previous = Carbon::parse($activeDays[$i]);
                    if ($current->diffInDays($previous) === 1) {
                        $currentStreak++;
                    } else {
                        break;
                    }
                }
            }
        }

        return [
            'current' => $currentStreak,
            'longest' => count($activeDays) > 0 ? $longestStreak : 0,
            'active_days' => $activeDays,
        ];
    }
}
