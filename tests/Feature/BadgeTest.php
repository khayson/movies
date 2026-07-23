<?php

use App\Models\User;
use App\Models\UserBadge;
use App\Services\BadgeService;

test('badge service awards first_watch badge', function () {
    $user = User::factory()->create();

    $user->watchHistory()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
    ]);

    app(BadgeService::class)->checkAndAward($user);

    expect($user->badges()->where('badge_key', 'first_watch')->exists())->toBeTrue();
});

test('badge service awards first_review badge', function () {
    $user = User::factory()->create();

    $user->reviews()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Great movie',
        'rating' => 9,
    ]);

    app(BadgeService::class)->checkAndAward($user);

    expect($user->badges()->where('badge_key', 'first_review')->exists())->toBeTrue();
});

test('badge service awards collector badge', function () {
    $user = User::factory()->create();

    $user->collections()->create([
        'name' => 'My List',
        'slug' => 'my-list-123',
    ]);

    app(BadgeService::class)->checkAndAward($user);

    expect($user->badges()->where('badge_key', 'collector')->exists())->toBeTrue();
});

test('badge service does not double-award badges', function () {
    $user = User::factory()->create();

    $user->watchHistory()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
    ]);

    $service = app(BadgeService::class);
    $service->checkAndAward($user);
    $service->checkAndAward($user);

    expect($user->badges()->where('badge_key', 'first_watch')->count())->toBe(1);
});

test('user badge has definition from config', function () {
    $badge = UserBadge::factory()->create(['badge_key' => 'first_watch']);

    $definition = $badge->definition();

    expect($definition)->not->toBeNull();
    expect($definition['name'])->toBe('First Watch');
});

test('badges are deleted when user is deleted', function () {
    $user = User::factory()->create();
    $user->badges()->create(['badge_key' => 'first_watch']);

    $user->delete();

    expect(UserBadge::where('badge_key', 'first_watch')->count())->toBe(0);
});
