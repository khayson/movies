<?php

use App\Models\User;
use App\Services\StreakService;

function watchedOn(User $user, int $tmdbId, int $daysAgo): void
{
    $user->watchHistory()->create([
        'tmdb_id' => $tmdbId,
        'media_type' => 'movie',
        'title' => 'Title '.$tmdbId,
    ])->forceFill(['updated_at' => now()->subDays($daysAgo)->setTime(20, 0)])->save();
}

test('consecutive viewing days build up the current and longest streak', function () {
    $user = User::factory()->create();

    foreach ([0, 1, 2] as $tmdbId => $daysAgo) {
        watchedOn($user, $tmdbId + 1, $daysAgo);
    }
    watchedOn($user, 10, 6);
    watchedOn($user, 11, 7);

    $streak = app(StreakService::class)->calculate($user);

    expect($streak['current'])->toBe(3)
        ->and($streak['longest'])->toBe(3)
        ->and($streak['active_days'])->toHaveCount(5);
});

test('the current streak is zero after a missed day', function () {
    $user = User::factory()->create();

    watchedOn($user, 1, 3);
    watchedOn($user, 2, 4);

    $streak = app(StreakService::class)->calculate($user);

    expect($streak['current'])->toBe(0)
        ->and($streak['longest'])->toBe(2);
});
