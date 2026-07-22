<?php

use App\Models\User;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [],
            'genres' => [],
            'total_pages' => 1,
            'id' => 550,
            'title' => 'Fight Club',
            'name' => 'Fight Club',
            'overview' => 'A test movie',
            'poster_path' => '/test.jpg',
            'backdrop_path' => '/test.jpg',
            'vote_average' => 8.4,
            'release_date' => '1999-10-15',
            'first_air_date' => '1999-10-15',
            'runtime' => 139,
            'status' => 'Released',
            'genres' => [['id' => 28, 'name' => 'Action']],
            'credits' => ['cast' => []],
            'videos' => ['results' => []],
            'similar' => ['results' => []],
            'recommendations' => ['results' => []],
            'seasons' => [],
        ]),
    ]);
});

test('user can see watchlist button on movie detail', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('movies.detail', 550))
        ->assertOk()
        ->assertSee('Watchlist');
});

test('user can see watchlist button on tv detail', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('tv.detail', 550))
        ->assertOk()
        ->assertSee('Watchlist');
});

test('watchlist items show on dashboard', function () {
    $user = User::factory()->create();
    Watchlist::factory()->create([
        'user_id' => $user->id,
        'title' => 'Test Watchlist Movie',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Test Watchlist Movie');
});

test('preferences settings page loads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('preferences.edit'))
        ->assertOk()
        ->assertSee('Preferences');
});

test('user can save preferences', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test('pages::settings.preferences')
        ->set('preferredType', 'movie')
        ->set('contentLanguage', 'es')
        ->call('savePreferences');

    $user->refresh();
    expect($user->preferences['preferred_type'])->toBe('movie');
    expect($user->preferences['content_language'])->toBe('es');
});
