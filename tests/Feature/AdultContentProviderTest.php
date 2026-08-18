<?php

use App\Services\AdultContentProvider;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'sources.rapidapi_key' => 'test-rapidapi-key',
        'sources.rapidapi_hosts.xnxx' => 'porn-xnxx-api.p.rapidapi.com',
        'sources.rapidapi_hosts.pornhub' => 'pornhub-api-xnxx.p.rapidapi.com',
        'sources.rapidapi_hosts.xvideos' => 'xvideos-com-api.p.rapidapi.com',
    ]);
});

test('xnxx trending falls back to amateur when the trending feed is empty', function () {
    Http::fake([
        'porn-xnxx-api.p.rapidapi.com/trending*' => Http::response(['results' => [], 'count' => 0]),
        'porn-xnxx-api.p.rapidapi.com/category' => Http::response([
            'count' => 1,
            'results' => [
                [
                    'title' => 'Studio Cut',
                    'video_link' => 'https://xnxx.com/video-abc/studio-cut',
                    'thumbnail' => 'https://example.com/thumb.jpg',
                    'views' => '1.2M',
                    'duration' => '12min',
                ],
            ],
        ]),
    ]);

    $catalog = app(AdultContentProvider::class)->xnxx(page: 1, mode: 'trending');

    expect($catalog['videos'])->toHaveCount(1)
        ->and($catalog['videos'][0]['title'])->toBe('Studio Cut')
        ->and($catalog['videos'][0]['provider'])->toBe('XNXX');
});

test('xnxx trending maps a successful feed', function () {
    Http::fake([
        'porn-xnxx-api.p.rapidapi.com/trending*' => Http::response([
            'count' => 1,
            'results' => [
                [
                    'title' => 'Amateur Night',
                    'video_link' => 'https://xnxx.com/video-def/amateur-night',
                    'thumbnail' => 'https://example.com/a.jpg',
                    'views' => '800k',
                    'duration' => '8min',
                ],
            ],
        ]),
    ]);

    $catalog = app(AdultContentProvider::class)->xnxx(page: 1, mode: 'trending');

    expect($catalog['videos'])->toHaveCount(1)
        ->and($catalog['videos'][0]['title'])->toBe('Amateur Night');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/trending'));
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/category'));
});

test('pornhub trending falls back to search when the trending feed is empty', function () {
    Http::fake([
        'pornhub-api-xnxx.p.rapidapi.com/api/trending*' => Http::response(['results' => [], 'count' => 0]),
        'pornhub-api-xnxx.p.rapidapi.com/api/search' => Http::response([
            'count' => 1,
            'results' => [
                [
                    'title' => 'Studio Cut',
                    'video_link' => 'https://pornhub.com/view_video.php?viewkey=abc',
                    'thumbnail' => 'https://example.com/ph.jpg',
                    'views' => '1M',
                    'duration' => '10min',
                ],
            ],
        ]),
    ]);

    $catalog = app(AdultContentProvider::class)->pornhub(page: 1, mode: 'trending');

    expect($catalog['videos'])->toHaveCount(1)
        ->and($catalog['videos'][0]['provider'])->toBe('PornHub');
});

test('pornhub maps a root-array trending feed', function () {
    Http::fake([
        'pornhub-api-xnxx.p.rapidapi.com/api/trending*' => Http::response([
            [
                'title' => 'Studio Cut',
                'url' => 'https://www.pornhub.com/view_video.php?viewkey=abc',
                'thumbnail' => 'https://example.com/ph.jpg',
                'views' => '1M',
                'duration' => '10:00',
            ],
        ]),
    ]);

    $catalog = app(AdultContentProvider::class)->pornhub(page: 1, mode: 'trending');

    expect($catalog['videos'])->toHaveCount(1)
        ->and($catalog['videos'][0]['video_link'])->toContain('viewkey=abc')
        ->and($catalog['videos'][0]['provider'])->toBe('PornHub');
});

test('pornhub search sends the pages parameter', function () {
    Http::fake([
        'pornhub-api-xnxx.p.rapidapi.com/api/search' => Http::response([
            [
                'title' => 'Studio Cut',
                'url' => 'https://www.pornhub.com/view_video.php?viewkey=abc',
                'thumbnail' => 'https://example.com/ph.jpg',
            ],
        ]),
    ]);

    app(AdultContentProvider::class)->pornhub(query: 'amateur', page: 2, mode: 'search');

    Http::assertSent(fn ($request) => ($request->data()['pages'] ?? null) === 2
        && ($request->data()['q'] ?? null) === 'amateur');
});

test('xvideos search uses keyword and maps french result fields', function () {
    Http::fake([
        'xvideos-com-api.p.rapidapi.com/search_video' => Http::response([
            'keyword' => 'amateur',
            'page' => 1,
            'total' => 22,
            'videos' => [
                [
                    'titre' => 'Studio Cut',
                    'lien' => 'https://www.xvideos.com/video123/studio',
                    'thumbnail' => 'https://example.com/xv.jpg',
                    'duree' => '10 min',
                    'vues' => '1M',
                ],
            ],
        ]),
    ]);

    $catalog = app(AdultContentProvider::class)->xvideos();

    expect($catalog['videos'])->toHaveCount(1)
        ->and($catalog['videos'][0]['title'])->toBe('Studio Cut')
        ->and($catalog['videos'][0]['video_link'])->toContain('video123')
        ->and($catalog['videos'][0]['provider'])->toBe('XVideos');

    Http::assertSent(fn ($request) => ($request->data()['keyword'] ?? null) === 'amateur');
});

test('eporner ignores leftover movie sorts and still returns a catalog', function () {
    Http::fake([
        'www.eporner.com/api/v2/video/search*' => Http::response([
            'total_count' => 1,
            'videos' => [
                [
                    'id' => 'abc',
                    'title' => 'Studio Cut',
                    'default_thumb' => ['src' => 'https://example.com/ep.jpg'],
                    'length_min' => '12:00',
                    'views' => 1000,
                    'rate' => 4.2,
                ],
            ],
        ]),
    ]);

    $catalog = app(AdultContentProvider::class)->eporner('', 1, 'popularity.desc');

    expect($catalog['videos'])->toHaveCount(1)
        ->and($catalog['videos'][0]['provider'])->toBe('Eporner');
});

test('redtube falls back to newest when the default feed is empty', function () {
    Http::fake(function ($request) {
        if (str_contains($request->url(), 'ordering=newest')) {
            return Http::response([
                'count' => 1,
                'videos' => [
                    [
                        'video' => [
                            'video_id' => 99,
                            'title' => 'Studio Cut',
                            'default_thumb' => 'https://example.com/rt.jpg',
                            'duration' => '10:00',
                            'views' => '1,000',
                            'rating' => 4,
                        ],
                    ],
                ],
            ]);
        }

        return Http::response(['count' => 0, 'videos' => []]);
    });

    $catalog = app(AdultContentProvider::class)->redtube();

    expect($catalog['videos'])->toHaveCount(1)
        ->and($catalog['videos'][0]['provider'])->toBe('RedTube');
});
