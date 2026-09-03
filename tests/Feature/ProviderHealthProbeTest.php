<?php

use App\Services\ProviderHealthProbe;
use App\Services\SourceResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('probe marks reachable providers healthy', function () {
    Http::fake([
        '*' => Http::response('', 200),
    ]);

    $stats = app(ProviderHealthProbe::class)->probeAll();

    expect($stats['checked'])->toBeGreaterThan(0)
        ->and($stats['healthy'])->toBe($stats['checked'])
        ->and($stats['unhealthy'])->toBe(0)
        ->and(app(ProviderHealthProbe::class)->isHealthy('VidCore'))->toBeTrue();
});

test('probe command caches health results', function () {
    Http::fake([
        '*' => Http::response('', 200),
    ]);

    $this->artisan('app:probe-provider-health')
        ->assertSuccessful()
        ->expectsOutputToContain('healthy');

    expect(Cache::get('provider_probe_results'))->toHaveKey('VidCore');
});

test('recommendServer demotes providers marked unhealthy by probe', function () {
    config(['sources.cinesrc.resolver_url' => null]);

    Cache::put('provider_probe_results', [
        'VidCore' => ['healthy' => false, 'checked_at' => time()],
    ], now()->addHour());

    $resolver = app(SourceResolver::class);
    $index = $resolver->recommendServer(550, 'movie', null, null);
    $sources = $resolver->resolve(550, 'movie');

    expect($sources[$index]['provider'])->not->toBe('VidCore');
});
