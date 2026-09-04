<?php

use App\Models\User;
use App\Services\AiRecommender;
use App\Services\RapidApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.rapidapi.key', 'test-rapidapi-key');
    Cache::flush();
});

test('search caches successful results for 3 days', function () {
    Http::fake([
        'ai-movie-recommender.p.rapidapi.com/*' => Http::response([
            'success' => true,
            'movies' => [
                ['id' => 550, 'title' => 'Fight Club'],
            ],
        ]),
    ]);

    $service = app(AiRecommender::class);
    $result = $service->search('action movies');

    expect($result['success'])->toBeTrue()
        ->and($result['movies'])->toHaveCount(1)
        ->and($result['movies'][0]['title'])->toBe('Fight Club');

    expect(Cache::has('ai_rec.search.'.md5('action movies')))->toBeTrue();
});

test('search does not cache empty or failed responses', function () {
    Http::fake([
        'ai-movie-recommender.p.rapidapi.com/*' => Http::response([
            'success' => true,
            'movies' => [],
        ]),
    ]);

    $service = app(AiRecommender::class);
    $result = $service->search('empty query');

    expect($result['success'])->toBeTrue()
        ->and($result['movies'])->toBeEmpty()
        ->and(Cache::has('ai_rec.search.'.md5('empty query')))->toBeFalse();
});

test('search returns empty movies on http failure', function () {
    Http::fake([
        'ai-movie-recommender.p.rapidapi.com/*' => Http::response([], 500),
    ]);

    $service = app(AiRecommender::class);
    $result = $service->search('nonexistent query');

    expect($result['success'])->toBeFalse()
        ->and($result['movies'])->toBeEmpty();
});

test('search marks unavailable when rapidapi key is missing', function () {
    Config::set('services.rapidapi.key', null);

    $service = app(AiRecommender::class);
    $result = $service->search('anything');

    expect($result['unavailable'])->toBeTrue()
        ->and($result['movies'])->toBeEmpty();

    Http::assertNothingSent();
});

test('rapid api client reports configured state', function () {
    expect(app(RapidApiClient::class)->configured())->toBeTrue();

    Config::set('services.rapidapi.key', '');

    expect(app(RapidApiClient::class)->configured())->toBeFalse();
});

test('mood picker page loads successfully', function () {
    $this->get('/mood')->assertSuccessful();
});

test('search page loads in standard mode', function () {
    $this->get('/search')->assertSuccessful();
});

test('search page loads in ai mode', function () {
    $this->get('/search?mode=ai')->assertSuccessful();
});

test('watch parties page explains playback is not synced', function () {
    $this->get('/watch-parties')
        ->assertSuccessful()
        ->assertSee('Playback is not synced', false);
});

test('user profile does not show premium badge', function () {
    $user = User::factory()->create([
        'is_premium' => true,
        'premium_until' => now()->addMonth(),
    ]);

    $this->actingAs($user)
        ->get(route('user.profile', $user->id))
        ->assertSuccessful()
        ->assertDontSee('>Premium</span>', false);
});
