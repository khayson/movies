<?php

use App\Models\User;
use App\Models\WatchHistory;
use App\Services\SourceResolver;
use Illuminate\Support\Facades\Http;

test('resolve includes personalized cinesrc embed as first source', function () {
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

    expect($sources)->not->toBeEmpty()
        ->and($sources[0]['provider'])->toBe('CineSrc')
        ->and($sources[0]['type'])->toBe('embed')
        ->and($sources[0]['url'])->toContain('https://cinesrc.st/embed/movie/550')
        ->and($sources[0]['url'])->toContain('t=600')
        ->and($sources[0]['url'])->toContain('lastserver=mirror-a')
        ->and($sources[0]['url'])->toContain('quality=1080');
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
