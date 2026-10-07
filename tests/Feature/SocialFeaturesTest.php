<?php

use App\Models\Activity;
use App\Models\AffiliateClick;
use App\Models\Follow;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WatchParty;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

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

    $response = $this->actingAs($user)
        ->get(route('user.profile', $user->id))
        ->assertOk();

    expect($response->getContent())->not->toContain('wire:click="toggleFollow"');
});

test('notifications page loads for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notifications'))
        ->assertOk();
});

test('notifications page loads for guest user', function () {
    $this->get(route('notifications'))
        ->assertOk()
        ->assertSee('Notifications');
});

test('notification dropdown renders for guest user with public notifications', function () {
    UserNotification::create([
        'user_id' => null,
        'type' => 'announcement',
        'title' => 'Guest Notification Test',
        'message' => 'Guest notification message text',
    ]);

    Livewire::test('notification-dropdown')
        ->assertSee('Guest Notification Test');
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

test('welcome notification is created on registration', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $user = User::where('email', 'newuser@example.com')->first();

    expect($user)->not->toBeNull();

    $notification = UserNotification::where('user_id', $user->id)
        ->where('type', 'account')
        ->first();

    expect($notification)->not->toBeNull();
    expect($notification->title)->toContain('Welcome');
    expect($notification->link)->toBe('/dashboard');
});

test('generate notifications command creates upcoming movie notifications', function () {
    Http::fake([
        '*/movie/upcoming*' => Http::response([
            'results' => [
                [
                    'id' => 999001,
                    'title' => 'Test Upcoming Movie',
                    'release_date' => '2027-01-15',
                    'poster_path' => '/test_poster.jpg',
                    'vote_average' => 8.0,
                ],
            ],
        ]),
        '*/movie/now_playing*' => Http::response([
            'results' => [
                [
                    'id' => 999002,
                    'title' => 'Test Now Playing Movie',
                    'release_date' => '2026-10-01',
                    'poster_path' => '/now_poster.jpg',
                    'vote_average' => 8.5,
                ],
            ],
        ]),
        '*' => Http::response(['results' => []]),
    ]);

    $this->artisan('app:generate-notifications', ['--limit' => 0])
        ->assertExitCode(0);

    expect(UserNotification::where('type', 'upcoming')->where('tmdb_id', 999001)->exists())->toBeTrue();
    expect(UserNotification::where('type', 'now_playing')->where('tmdb_id', 999002)->exists())->toBeTrue();
});

test('generate notifications command creates personalized recommendations', function () {
    $user = User::factory()->create();

    \App\Models\WatchHistory::factory()->create([
        'user_id' => $user->id,
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
    ]);

    Http::fake([
        '*/movie/upcoming*' => Http::response(['results' => []]),
        '*/movie/now_playing*' => Http::response(['results' => []]),
        '*/movie/550*' => Http::response([
            'id' => 550,
            'title' => 'Fight Club',
            'genres' => [
                ['id' => 18, 'name' => 'Drama'],
                ['id' => 53, 'name' => 'Thriller'],
            ],
        ]),
        '*/discover/movie*' => Http::response([
            'results' => [
                [
                    'id' => 999003,
                    'title' => 'Recommended Movie',
                    'poster_path' => '/rec_poster.jpg',
                    'vote_average' => 7.8,
                ],
            ],
        ]),
        '*' => Http::response(['results' => []]),
    ]);

    $this->artisan('app:generate-notifications', ['--limit' => 1])
        ->assertExitCode(0);

    expect(UserNotification::where('type', 'recommendation')
        ->where('user_id', $user->id)
        ->where('tmdb_id', 999003)
        ->exists())->toBeTrue();
});

test('notification scopes work for visitors and filters', function () {
    $user = User::factory()->create();

    UserNotification::create([
        'user_id' => $user->id,
        'type' => 'recommendation',
        'title' => 'Recommended: Some Movie',
        'message' => 'Based on your watch history.',
    ]);

    UserNotification::create([
        'user_id' => null,
        'type' => 'upcoming',
        'title' => 'Upcoming: Future Movie',
        'message' => 'Coming soon!',
    ]);

    $allForUser = UserNotification::forVisitor($user->id)->get();
    expect($allForUser->pluck('title'))->toContain('Recommended: Some Movie', 'Upcoming: Future Movie');

    $allForGuest = UserNotification::forVisitor(null)->get();
    expect($allForGuest->pluck('title'))->toContain('Upcoming: Future Movie')
        ->not->toContain('Recommended: Some Movie');
});
