<?php

use App\Models\Activity;
use App\Models\AffiliateClick;
use App\Models\Follow;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WatchParty;
use App\Services\ActivityLogger;

test('user can follow another user', function () {
    $follower = User::factory()->create();
    $target = User::factory()->create();

    Follow::create([
        'follower_id' => $follower->id,
        'following_id' => $target->id,
    ]);

    expect($follower->isFollowing($target))->toBeTrue();
    expect($target->followers()->count())->toBe(1);
});

test('user cannot follow themselves', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user.profile', $user->id))
        ->assertDontSee('Follow');
});

test('notifications page loads for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notifications'))
        ->assertOk();
});

test('notification can be marked as read', function () {
    $notification = UserNotification::factory()->create();

    expect($notification->read_at)->toBeNull();

    $notification->markAsRead();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('activity feed page loads', function () {
    $this->get(route('activity.feed'))
        ->assertOk();
});

test('activity logger creates activity', function () {
    $user = User::factory()->create();
    $logger = new ActivityLogger;

    $activity = $logger->log(
        $user,
        'review',
        'wrote a review',
        12345,
        'movie',
        'Test Movie',
    );

    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->type)->toBe('review');
    expect($activity->user_id)->toBe($user->id);
});

test('watch parties page loads', function () {
    $this->get(route('watch-parties'))
        ->assertOk();
});

test('watch party generates unique code', function () {
    $party = WatchParty::factory()->create();

    expect($party->code)->toHaveLength(8);
});

test('affiliate click is recorded', function () {
    $click = AffiliateClick::factory()->create();

    expect($click->service_name)->not->toBeEmpty();
    expect(AffiliateClick::count())->toBe(1);
});

test('user profile shows follower count', function () {
    $user = User::factory()->create();
    $follower = User::factory()->create();

    Follow::create([
        'follower_id' => $follower->id,
        'following_id' => $user->id,
    ]);

    $this->get(route('user.profile', $user->id))
        ->assertOk()
        ->assertSeeInOrder(['1', 'follower']);
});

test('premium user check works', function () {
    $user = User::factory()->create(['is_premium' => true, 'premium_until' => now()->addMonth()]);

    expect($user->isPremium())->toBeTrue();

    $expired = User::factory()->create(['is_premium' => true, 'premium_until' => now()->subDay()]);

    expect($expired->isPremium())->toBeFalse();
});
