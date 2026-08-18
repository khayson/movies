<?php

use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [],
            'total_pages' => 1,
            'id' => 550,
            'title' => 'Fight Club',
            'adult' => true,
            'poster_path' => '/test.jpg',
        ]),
        'api.tmdb.org/*' => Http::response([
            'results' => [],
            'total_pages' => 1,
            'id' => 550,
            'title' => 'Fight Club',
            'adult' => true,
            'poster_path' => '/test.jpg',
        ]),
        '*' => Http::response(['results' => [], 'movies' => [], 'shows' => [], 'data' => []]),
    ]);
});

/**
 * @param  array<string, mixed>  $preferences
 */
function adultUser(array $preferences = []): User
{
    return User::factory()->create([
        'date_of_birth' => now()->subYears(25),
        'preferences' => array_merge([
            'show_adult_content' => true,
            'adult_lock_session' => false,
        ], $preferences),
    ]);
}

test('guests cannot access adult browse page', function () {
    $this->get(route('adult.browse'))->assertRedirect(route('login'));
});

test('underage users are blocked from adult browse page', function () {
    $user = User::factory()->create([
        'date_of_birth' => now()->subYears(16),
        'preferences' => ['show_adult_content' => true, 'adult_lock_session' => false],
    ]);

    $this->actingAs($user)
        ->get(route('adult.browse'))
        ->assertForbidden();
});

test('adult users without preference enabled are blocked', function () {
    $user = User::factory()->create([
        'date_of_birth' => now()->subYears(25),
        'preferences' => ['show_adult_content' => false],
    ]);

    $this->actingAs($user)
        ->get(route('adult.browse'))
        ->assertForbidden();
});

test('verified adult users can access adult browse page', function () {
    $this->actingAs(adultUser())
        ->get(route('adult.browse'))
        ->assertOk()
        ->assertSee('Adult vault')
        ->assertSee('Movies')
        ->assertDontSee('Teen');
});

test('adult vault requires password confirmation by default', function () {
    $user = User::factory()->create([
        'date_of_birth' => now()->subYears(25),
        'preferences' => ['show_adult_content' => true],
    ]);

    $this->actingAs($user)
        ->get(route('adult.browse'))
        ->assertRedirect(route('password.confirm'));
});

test('password-confirmed adults can open the locked vault', function () {
    $user = User::factory()->create([
        'date_of_birth' => now()->subYears(25),
        'preferences' => ['show_adult_content' => true],
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('adult.browse'))
        ->assertOk();
});

test('user without date of birth cannot enable adult content', function () {
    $user = User::factory()->create([
        'date_of_birth' => null,
        'preferences' => null,
    ]);

    expect($user->canViewAdultContent())->toBeFalse();
    expect($user->isAdult())->toBeFalse();
});

test('adult link only visible in navbar for verified adult users', function () {
    $this->actingAs(adultUser())
        ->get(route('home'))
        ->assertSee('18+');

    $regularUser = User::factory()->create([
        'date_of_birth' => null,
        'preferences' => null,
    ]);

    $this->actingAs($regularUser)
        ->get(route('home'))
        ->assertDontSee('18+');
});

test('stealth mode uses a discreet vault nav link', function () {
    $user = adultUser(['adult_stealth_mode' => true]);

    expect($user->adultStealthEnabled())->toBeTrue();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Private vault', false)
        ->assertSee(route('adult.browse'), false);
});

test('blocked adult searches are discarded', function () {
    $this->actingAs(adultUser());

    Livewire::test('pages::adult-browse')
        ->set('search', 'teen')
        ->call('searchVideos')
        ->assertSet('search', '');
});

test('stealth mode marks adult watches as private', function () {
    $user = adultUser(['adult_stealth_mode' => true]);

    $this->actingAs($user);

    Livewire::test('pages::watch-page', [
        'type' => 'movie',
        'tmdbId' => 550,
    ]);

    $history = WatchHistory::query()
        ->where('user_id', $user->id)
        ->where('tmdb_id', 550)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->is_private)->toBeTrue()
        ->and($user->watchHistory()->visible()->count())->toBe(0);
});
