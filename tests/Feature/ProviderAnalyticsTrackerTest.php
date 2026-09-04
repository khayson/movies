<?php

use App\Models\ProviderAnalytic;
use App\Services\ProviderAnalyticsTracker;
use Illuminate\Http\Request;

test('detectRegion prefers cloudflare country header', function () {
    $request = Request::create('/', 'GET', server: [
        'HTTP_CF_IPCOUNTRY' => 'GH',
        'REMOTE_ADDR' => '8.8.8.8',
    ]);
    $this->app->instance('request', $request);

    $region = app(ProviderAnalyticsTracker::class)->detectRegion();

    expect($region)->toBe('GH');
});

test('detectRegion falls back to local for loopback without country header', function () {
    $request = Request::create('/', 'GET', server: [
        'REMOTE_ADDR' => '127.0.0.1',
    ]);
    $this->app->instance('request', $request);

    $region = app(ProviderAnalyticsTracker::class)->detectRegion();

    expect($region)->toBe('local');
});

test('slo summary ranks providers by recent success rate', function () {
    ProviderAnalytic::factory()->create([
        'provider' => 'VidCore',
        'success_count' => 90,
        'failure_count' => 10,
        'buffer_count' => 2,
        'date' => now()->toDateString(),
        'hour_bucket' => 12,
        'region' => 'local',
    ]);

    ProviderAnalytic::factory()->create([
        'provider' => 'VidSrc',
        'success_count' => 40,
        'failure_count' => 60,
        'buffer_count' => 20,
        'date' => now()->toDateString(),
        'hour_bucket' => 12,
        'region' => 'local',
    ]);

    $summary = app(ProviderAnalyticsTracker::class)->sloSummary();

    expect($summary)->not->toBeEmpty()
        ->and($summary[0]['provider'])->toBe('VidCore')
        ->and($summary[0]['success_rate'])->toBe(90.0)
        ->and(collect($summary)->firstWhere('provider', 'VidSrc')['success_rate'])->toBe(40.0);
});
