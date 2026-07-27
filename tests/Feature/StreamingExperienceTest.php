<?php

use App\Models\ProviderAnalytic;
use App\Models\User;
use App\Models\WatchHistory;
use App\Services\SourceResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [],
            'genres' => [],
            'total_pages' => 1,
            'id' => 550,
            'title' => 'Fight Club',
            'name' => 'Breaking Bad',
            'overview' => 'A test show',
            'poster_path' => '/test.jpg',
            'backdrop_path' => '/test.jpg',
            'vote_average' => 9.5,
            'release_date' => '2020-01-01',
            'first_air_date' => '2008-01-20',
            'runtime' => 139,
            'status' => 'Released',
            'genres' => [['id' => 18, 'name' => 'Drama']],
            'credits' => ['cast' => []],
            'videos' => ['results' => []],
            'similar' => ['results' => []],
            'recommendations' => ['results' => []],
            'number_of_seasons' => 5,
            'seasons' => [],
            'episodes' => [
                ['episode_number' => 1, 'name' => 'Pilot', 'runtime' => 58, 'still_path' => '/ep1.jpg', 'overview' => 'Test'],
                ['episode_number' => 2, 'name' => 'Cat in the Bag', 'runtime' => 48, 'still_path' => '/ep2.jpg', 'overview' => 'Test 2'],
            ],
        ]),
    ]);
});

test('watch page loads for movie', function () {
    $this->get(route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertOk()
        ->assertSee('Disclaimer');
});

test('watch page loads for tv episode', function () {
    $this->get(route('watch', ['type' => 'tv', 'tmdbId' => 1396, 'season' => 1, 'episode' => 1]))
        ->assertOk();
});

test('watch page has keyboard shortcuts panel', function () {
    $this->get(route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertOk()
        ->assertSee('Keyboard Shortcuts', escape: false);
});

test('watch page has pip button', function () {
    $this->get(route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertOk()
        ->assertSee('PiP', escape: false);
});

test('watch page has auto-next countdown for tv', function () {
    $this->get(route('watch', ['type' => 'tv', 'tmdbId' => 1396, 'season' => 1, 'episode' => 1]))
        ->assertOk()
        ->assertSee('Up Next', escape: false);
});

test('watch page has resume prompt markup', function () {
    $this->get(route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertOk()
        ->assertSee('Resume where you left off', escape: false);
});

test('authenticated user progress is saved with device name', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test('pages::watch-page', [
        'type' => 'movie',
        'tmdbId' => 550,
    ])
        ->call('saveProgress', 120, 7200, 'Windows');

    $history = WatchHistory::where('user_id', $user->id)
        ->where('tmdb_id', 550)
        ->first();

    expect($history)->not->toBeNull();
    expect($history->progress_seconds)->toBe(120);
    expect($history->duration_seconds)->toBe(7200);
    expect($history->device_name)->toBe('Windows');
    expect($history->last_watched_at)->not->toBeNull();
});

test('server error triggers fallback recommendation', function () {
    $resolver = app(SourceResolver::class);

    $resolver->reportFailure('VidCore');
    $failed = Cache::get('failed_providers', []);

    expect($failed)->toHaveKey('VidCore');
});

test('server health indicator uses failed providers cache', function () {
    Cache::put('failed_providers', ['TestProvider' => time()], now()->addMinutes(30));

    $failed = Cache::get('failed_providers', []);
    expect($failed)->toHaveKey('TestProvider');
});

test('watch page has auto-fallback overlay', function () {
    $this->get(route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertOk()
        ->assertSee('Switching server', escape: false);
});

test('provider analytics are recorded on failure', function () {
    $resolver = app(SourceResolver::class);

    $resolver->reportFailure('VidCore');

    $analytic = ProviderAnalytic::where('provider', 'VidCore')->first();
    expect($analytic)->not->toBeNull();
    expect($analytic->failure_count)->toBe(1);
});

test('provider analytics are recorded on success', function () {
    $resolver = app(SourceResolver::class);

    $resolver->reportSuccess('CineSrc');

    $analytic = ProviderAnalytic::where('provider', 'CineSrc')->first();
    expect($analytic)->not->toBeNull();
    expect($analytic->success_count)->toBe(1);
});

test('provider analytics track buffering events', function () {
    $resolver = app(SourceResolver::class);

    $resolver->reportBuffering('VidSrc', 3500);

    $analytic = ProviderAnalytic::where('provider', 'VidSrc')->first();
    expect($analytic)->not->toBeNull();
    expect($analytic->buffer_count)->toBe(1);
});

test('provider health returns scores for known providers', function () {
    $resolver = app(SourceResolver::class);

    $health = $resolver->getProviderHealth();

    expect($health)->toHaveKey('CineSrc');
    expect($health['CineSrc'])->toBe(100);
});

test('failed provider has reduced health score', function () {
    $resolver = app(SourceResolver::class);

    $resolver->reportFailure('VidCore');
    $health = $resolver->getProviderHealth();

    expect($health['VidCore'])->toBeLessThan(100);
});

test('provider analytic success rate calculation', function () {
    $analytic = ProviderAnalytic::factory()->create([
        'success_count' => 80,
        'failure_count' => 20,
    ]);

    expect($analytic->successRate())->toBe(80.0);
});

test('pre-warm sources command runs', function () {
    $this->artisan('app:pre-warm-sources')
        ->assertSuccessful();
});

test('watch page reports buffering via livewire', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test('pages::watch-page', [
        'type' => 'movie',
        'tmdbId' => 550,
    ])
        ->call('reportBuffering', 2000);

    expect(ProviderAnalytic::count())->toBeGreaterThanOrEqual(1);
});

test('watch page reports server success via livewire', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire\Livewire::test('pages::watch-page', [
        'type' => 'movie',
        'tmdbId' => 550,
    ])
        ->call('reportServerSuccess');

    expect(ProviderAnalytic::count())->toBeGreaterThanOrEqual(1);
});
