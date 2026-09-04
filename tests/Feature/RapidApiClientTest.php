<?php

use App\Services\RapidApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.rapidapi.key', 'test-rapidapi-key');
    Cache::flush();
});

test('getJson returns payload for successful responses', function () {
    Http::fake([
        'example.p.rapidapi.com/*' => Http::response(['ok' => true, 'id' => 1]),
    ]);

    $payload = app(RapidApiClient::class)->getJson(
        'example.p.rapidapi.com',
        'https://example.p.rapidapi.com/item',
    );

    expect($payload)->toBe(['ok' => true, 'id' => 1]);
});

test('circuit opens after repeated server failures and blocks further calls', function () {
    Http::fake([
        'broken.p.rapidapi.com/*' => Http::response(['error' => true], 503),
    ]);

    $client = app(RapidApiClient::class);

    for ($i = 0; $i < 5; $i++) {
        expect($client->getJson(
            'broken.p.rapidapi.com',
            'https://broken.p.rapidapi.com/item',
        ))->toBeNull();
    }

    expect($client->circuitOpen('broken.p.rapidapi.com'))->toBeTrue();

    Http::fake();

    expect($client->getJson(
        'broken.p.rapidapi.com',
        'https://broken.p.rapidapi.com/item',
    ))->toBeNull();

    Http::assertNothingSent();
});

test('successful response clears the circuit failure state', function () {
    Http::fake([
        'ok.p.rapidapi.com/*' => Http::sequence()
            ->push(['error' => true], 503)
            ->push(['error' => true], 503)
            ->push(['ok' => true]),
    ]);

    $client = app(RapidApiClient::class);

    $client->getJson('ok.p.rapidapi.com', 'https://ok.p.rapidapi.com/a');
    $client->getJson('ok.p.rapidapi.com', 'https://ok.p.rapidapi.com/b');
    $result = $client->getJson('ok.p.rapidapi.com', 'https://ok.p.rapidapi.com/c');

    expect($result)->toBe(['ok' => true])
        ->and($client->circuitOpen('ok.p.rapidapi.com'))->toBeFalse();
});

test('getJson returns null when key is missing', function () {
    Config::set('services.rapidapi.key', null);

    expect(app(RapidApiClient::class)->getJson(
        'example.p.rapidapi.com',
        'https://example.p.rapidapi.com/item',
    ))->toBeNull();

    Http::assertNothingSent();
});
