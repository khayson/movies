<?php

use App\Models\User;
use App\Models\WatchHistory;
use App\Services\SourceResolver;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('resolve includes personalized cinesrc embed source', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $user = User::factory()->create([
        'preferences' => [
            'stream_quality' => '1080',
            'cinesrc_autoskip' => true,
            'cinesrc_autonext' => false,
        ],
    ]);

    WatchHistory::factory()->create([
        'user_id' => $user->id,
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'progress_seconds' => 600,
        'cinesrc_server_id' => 'mirror-a',
    ]);

    $this->actingAs($user);

    $sources = app(SourceResolver::class)->resolve(550, 'movie');
    $cinesrc = collect($sources)->firstWhere('provider', 'CineSrc');

    expect($cinesrc)->not->toBeNull()
        ->and($sources[0]['provider'])->toBe('VidCore')
        ->and($sources[0]['url'])->toContain('autoPlay=true')
        ->and($sources[0]['url'])->toContain('startAt=600')
        ->and($sources[0]['supports_postmessage'])->toBeTrue()
        ->and($sources[0]['postmessage']['protocol'])->toBe('vidcore')
        ->and($cinesrc['type'])->toBe('embed')
        ->and($cinesrc['url'])->toContain('https://cinesrc.st/embed/movie/550')
        ->and($cinesrc['url'])->toContain('t=600')
        ->and($cinesrc['url'])->toContain('lastserver=mirror-a')
        ->and($cinesrc['url'])->toContain('quality=1080');
});

test('cinesrc tv embed respects autonext and autoskip preferences', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $user = User::factory()->create([
        'preferences' => [
            'cinesrc_autoskip' => true,
            'cinesrc_autonext' => false,
        ],
    ]);

    $this->actingAs($user);

    $sources = app(SourceResolver::class)->resolve(1396, 'tv', 1, 1);
    $cinesrc = collect($sources)->firstWhere('provider', 'CineSrc');

    expect($cinesrc)->not->toBeNull()
        ->and($cinesrc['url'])->toContain('autoskip=true')
        ->and($cinesrc['url'])->toContain('autonext=false');
});

test('resolve adds cinesrc direct hls source when resolver is configured', function () {
    config(['sources.cinesrc.resolver_url' => 'http://127.0.0.1:8787']);

    Http::fake([
        '127.0.0.1:8787/api/stream/live*' => Http::response(
            "event: ready\ndata: {\"playUrl\":\"http://127.0.0.1:8787/api/proxy?url=https%3A%2F%2Fcdn.example.com%2Fstream.m3u8\",\"quality\":\"1080\",\"source\":\"feb\",\"name\":\"FebBox\"}\n\n",
            200,
            ['Content-Type' => 'text/event-stream'],
        ),
    ]);

    $sources = app(SourceResolver::class)->resolve(550, 'movie');

    expect(collect($sources)->firstWhere('provider', 'CineSrc Direct'))->not->toBeNull()
        ->and(collect($sources)->firstWhere('provider', 'CineSrc Direct')['type'])->toBe('hls')
        ->and(collect($sources)->firstWhere('provider', 'CineSrc Direct')['url'])->toContain('stream.m3u8');
});

test('recommendServer prefers user default source preference', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $user = User::factory()->create([
        'preferences' => ['default_source' => 'VidCore'],
    ]);

    $this->actingAs($user);

    $index = app(SourceResolver::class)->recommendServer(550, 'movie', null, null);
    $sources = app(SourceResolver::class)->resolve(550, 'movie');

    expect($sources[$index]['provider'])->toBe('VidCore');
});

test('excluded providers are filtered from resolve', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $user = User::factory()->create([
        'preferences' => ['excluded_providers' => ['VidCore', 'CineSrc']],
    ]);

    $this->actingAs($user);

    $providers = collect(app(SourceResolver::class)->resolve(550, 'movie'))->pluck('provider');

    expect($providers)->not->toContain('VidCore')
        ->and($providers)->not->toContain('CineSrc');
});

test('remember last server off skips cinesrc lastserver injection', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $user = User::factory()->create([
        'preferences' => ['remember_last_server' => false],
    ]);

    WatchHistory::factory()->create([
        'user_id' => $user->id,
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'progress_seconds' => 600,
        'cinesrc_server_id' => 'mirror-a',
    ]);

    $this->actingAs($user);

    $cinesrc = collect(app(SourceResolver::class)->resolve(550, 'movie'))->firstWhere('provider', 'CineSrc');

    expect($cinesrc['url'])->toContain('t=600')
        ->and($cinesrc['url'])->not->toContain('lastserver=mirror-a');
});

test('resume prompt preference is passed to cinesrc embed', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $user = User::factory()->create([
        'preferences' => ['resume_prompt' => false],
    ]);

    WatchHistory::factory()->create([
        'user_id' => $user->id,
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'progress_seconds' => 600,
    ]);

    $this->actingAs($user);

    $cinesrc = collect(app(SourceResolver::class)->resolve(550, 'movie'))->firstWhere('provider', 'CineSrc');

    expect($cinesrc['url'])->toContain('continueprompt=false');
});

test('prefer hls direct boosts cinesrc direct recommendation', function () {
    config(['sources.cinesrc.resolver_url' => 'http://127.0.0.1:8787']);

    Http::fake([
        '127.0.0.1:8787/api/stream/live*' => Http::response(
            "event: ready\ndata: {\"playUrl\":\"http://127.0.0.1:8787/api/proxy?url=https%3A%2F%2Fcdn.example.com%2Fstream.m3u8\",\"quality\":\"1080\"}\n\n",
            200,
            ['Content-Type' => 'text/event-stream'],
        ),
    ]);

    $user = User::factory()->create([
        'preferences' => ['prefer_hls_direct' => true],
    ]);

    $this->actingAs($user);

    $index = app(SourceResolver::class)->recommendServer(550, 'movie', null, null);
    $sources = app(SourceResolver::class)->resolve(550, 'movie');

    expect($sources[$index]['provider'])->toBe('CineSrc Direct');
});

test('recommendServer rotates defaults across providers without user preference', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $resolver = app(SourceResolver::class);

    $items = [
        [550, 'movie', null, null],
        [1396, 'tv', 1, 1],
        [603, 'movie', null, null],
        [27205, 'movie', null, null],
    ];

    $providers = collect($items)->map(function (array $item) use ($resolver): string {
        [$tmdbId, $mediaType, $season, $episode] = $item;
        $index = $resolver->recommendServer($tmdbId, $mediaType, $season, $episode);
        $sources = $resolver->resolve($tmdbId, $mediaType, $season, $episode);

        return $sources[$index]['provider'] ?? '';
    });

    expect($providers->unique()->count())->toBeGreaterThan(1);
});

test('recommendServer excludes failed provider on fallback', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $resolver = app(SourceResolver::class);
    $sources = $resolver->resolve(550, 'movie');
    $vidCoreIndex = collect($sources)->search(fn (array $source): bool => ($source['provider'] ?? '') === 'VidCore');

    expect($vidCoreIndex)->not->toBeFalse();

    $next = $resolver->recommendServer(550, 'movie', null, null, 'VidCore');
    $nextProvider = $sources[$next]['provider'] ?? null;

    expect($nextProvider)->not->toBe('VidCore');
});

test('watch page reportServerError respects auto fallback preference', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    $user = User::factory()->create([
        'preferences' => ['auto_fallback_on_error' => false],
    ]);

    $this->actingAs($user);

    $component = Livewire::test('pages::watch-page', [
        'type' => 'movie',
        'tmdbId' => 550,
    ]);

    $active = $component->get('activeServer');
    $component->call('reportServerError', $active);

    expect($component->get('activeServer'))->toBe($active);
});
