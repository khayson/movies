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
        ->assertSee('Preferences')
        ->assertSee('Advanced')
        ->assertSee('Search settings', false)
        ->assertSee('id="settings-search"', false);
});

test('user can save preferences', function () {
    $user = User::factory()->create([
        'preferences' => ['custom_flag' => 'keep-me'],
    ]);

    $this->actingAs($user);

    Livewire\Livewire::test('pages::settings.preferences')
        ->set('preferredType', 'movie')
        ->set('contentLanguage', 'es')
        ->set('streamQuality', '1080')
        ->set('cinesrcAutoskip', true)
        ->set('cinesrcAutonext', false)
        ->set('preferHlsDirect', true)
        ->set('autoplayOnWatch', false)
        ->set('startMuted', true)
        ->set('resumePrompt', false)
        ->set('cinesrcSeek', 15)
        ->set('rememberLastServer', false)
        ->set('autoFallbackOnError', false)
        ->set('excludedProviders', ['VidCore'])
        ->call('savePreferences');

    $user->refresh();
    expect($user->preferences['preferred_type'])->toBe('movie');
    expect($user->preferences['content_language'])->toBe('es');
    expect($user->preferences['stream_quality'])->toBe('1080');
    expect($user->preferences['cinesrc_autoskip'])->toBeTrue();
    expect($user->preferences['cinesrc_autonext'])->toBeFalse();
    expect($user->preferences['prefer_hls_direct'])->toBeTrue();
    expect($user->preferences['autoplay_on_watch'])->toBeFalse();
    expect($user->preferences['start_muted'])->toBeTrue();
    expect($user->preferences['resume_prompt'])->toBeFalse();
    expect($user->preferences['cinesrc_seek'])->toBe(15);
    expect($user->preferences['remember_last_server'])->toBeFalse();
    expect($user->preferences['auto_fallback_on_error'])->toBeFalse();
    expect($user->preferences['excluded_providers'])->toBe(['VidCore']);
    expect($user->preferences['custom_flag'])->toBe('keep-me');
});

test('user can reset preferences to defaults', function () {
    $user = User::factory()->create([
        'preferences' => [
            'preferred_type' => 'tv',
            'remember_last_server' => false,
            'custom_flag' => 'keep-me',
        ],
    ]);

    $this->actingAs($user);

    Livewire\Livewire::test('pages::settings.preferences')
        ->call('resetPreferences');

    $user->refresh();
    expect($user->preferences['preferred_type'])->toBe('all');
    expect($user->preferences['remember_last_server'])->toBeTrue();
    expect($user->preferences['custom_flag'])->toBe('keep-me');
});
