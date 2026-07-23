<?php

use App\Services\AiRecommender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('search caches results for 3 days', function () {
    Http::fake([
        'ai-movie-recommender.p.rapidapi.com/*' => Http::response([
            'success' => true,
            'movies' => [
                ['id' => 550, 'title' => 'Fight Club'],
            ],
        ]),
    ]);

    $service = new AiRecommender;
    $result = $service->search('action movies');

    expect($result['success'])->toBeTrue()
        ->and($result['movies'])->toHaveCount(1)
        ->and($result['movies'][0]['title'])->toBe('Fight Club');

    expect(Cache::has('ai_rec.search.'.md5('action movies')))->toBeTrue();
});

test('search returns empty movies on failure', function () {
    Http::fake([
        'ai-movie-recommender.p.rapidapi.com/*' => Http::response([], 500),
    ]);

    Cache::flush();

    $service = new AiRecommender;
    $result = $service->search('nonexistent query');

    expect($result['success'])->toBeFalse()
        ->and($result['movies'])->toBeEmpty();
});

test('mood picker page loads successfully', function () {
    $this->get('/mood')->assertStatus(200);
});

test('search page loads in standard mode', function () {
    $this->get('/search')->assertStatus(200);
});

test('search page loads in ai mode', function () {
    $this->get('/search?mode=ai')->assertStatus(200);
});
