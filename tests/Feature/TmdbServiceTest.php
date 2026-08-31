<?php

use App\Services\Tmdb;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('tmdb service fetches popular movies', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/popular*' => Http::response([
            'results' => [
                ['id' => 1, 'title' => 'Test Movie'],
            ],
            'total_pages' => 1,
        ]),
    ]);

    $tmdb = app(Tmdb::class);
    $result = $tmdb->popular('movie');

    expect($result['results'])->toHaveCount(1);
    expect($result['results'][0]['title'])->toBe('Test Movie');
});

test('tmdb service fetches movie details', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/550*' => Http::response([
            'id' => 550,
            'title' => 'Fight Club',
            'overview' => 'A test overview',
            'credits' => ['cast' => []],
            'videos' => ['results' => []],
            'similar' => ['results' => []],
            'recommendations' => ['results' => []],
        ]),
    ]);

    $tmdb = app(Tmdb::class);
    $result = $tmdb->details('movie', 550);

    expect($result['title'])->toBe('Fight Club');
    expect($result['id'])->toBe(550);
});

test('tmdb service searches across movie and tv', function () {
    Http::fake([
        'api.themoviedb.org/3/search/multi*' => Http::response([
            'results' => [
                ['id' => 1, 'title' => 'Breaking Bad', 'media_type' => 'tv'],
            ],
            'total_pages' => 1,
        ]),
    ]);

    $tmdb = app(Tmdb::class);
    $result = $tmdb->search('breaking');

    expect($result['results'])->toHaveCount(1);
});

test('tmdb service generates correct image urls', function () {
    $tmdb = app(Tmdb::class);

    expect($tmdb->imageUrl('/test.jpg', 'w500'))
        ->toBe('https://image.tmdb.org/t/p/w500/test.jpg');

    expect($tmdb->backdropUrl('/bg.jpg'))
        ->toBe('https://image.tmdb.org/t/p/original/bg.jpg');
});

test('tmdb service retries then recovers from connection failures', function () {
    $attempts = 0;

    Http::fake(function () use (&$attempts) {
        $attempts++;

        if ($attempts < 2) {
            return Http::failedConnection('Connection was reset');
        }

        return Http::response([
            'id' => 980431,
            'title' => 'Recovered Movie',
        ]);
    });

    $result = app(Tmdb::class)->details('movie', 980431);

    expect($attempts)->toBe(2)
        ->and($result['title'])->toBe('Recovered Movie');
});

test('tmdb service fails over to a backup tmdb host', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tmdb.org/3/movie/popular*' => Http::response([
            'results' => [
                ['id' => 2, 'title' => 'Fallback Host Movie'],
            ],
        ]),
    ]);

    $result = app(Tmdb::class)->popular('movie');

    expect($result['results'][0]['title'])->toBe('Fallback Host Movie');
});

test('tmdb service serves stale cache when the upstream keeps failing', function () {
    Cache::put('tmdb.'.md5('/movie/980431'.serialize([
        'append_to_response' => 'credits,videos,similar,recommendations,reviews,external_ids',
        'include_adult' => false,
    ])).'.stale', [
        'id' => 980431,
        'title' => 'Cached Movie',
    ], now()->addDay());

    Http::fake([
        'api.themoviedb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tmdb.org/*' => Http::failedConnection('Connection was reset'),
    ]);

    $result = app(Tmdb::class)->details('movie', 980431);

    expect($result['title'])->toBe('Cached Movie');
});

test('tmdb details return an empty payload instead of a results list when catalogs are down', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tmdb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tvmaze.com/*' => Http::response(['message' => 'Not Found'], 404),
    ]);

    $result = app(Tmdb::class)->details('tv', 108978);

    expect($result)->toBe([])
        ->and($result)->not->toHaveKey('id');
});

test('tv detail returns 404 instead of crashing when catalogs have no show', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tmdb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tvmaze.com/*' => Http::response(['message' => 'Not Found'], 404),
        '*' => Http::response(['results' => []], 200),
    ]);

    $this->get(route('tv.detail', 108978))->assertNotFound();
});

test('tmdb service returns empty results instead of 503 when there is no cache', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tmdb.org/*' => Http::failedConnection('Connection was reset'),
    ]);

    $result = app(Tmdb::class)->popular('movie');

    expect($result['results'])->toBe([]);
});

test('tmdb service uses tvmaze when tmdb hosts are down for tv lists', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tmdb.org/*' => Http::failedConnection('Connection was reset'),
        'api.tvmaze.com/shows*' => Http::response([
            [
                'name' => 'Breaking Bad',
                'summary' => '<p>A chemistry teacher.</p>',
                'premiered' => '2008-01-20',
                'image' => ['medium' => 'https://static.tvmaze.com/bb.jpg', 'original' => 'https://static.tvmaze.com/bb-lg.jpg'],
                'rating' => ['average' => 9.3],
                'externals' => ['tmdb' => 1396, 'imdb' => 'tt0903747'],
            ],
            [
                'name' => 'No TMDB Mapping',
                'externals' => [],
            ],
        ]),
    ]);

    $result = app(Tmdb::class)->popular('tv');

    expect($result['results'])->toHaveCount(1)
        ->and($result['results'][0]['id'])->toBe(1396)
        ->and($result['results'][0]['media_type'])->toBe('tv')
        ->and($result['results'][0]['name'])->toBe('Breaking Bad');
});

test('tmdb image urls pass through absolute fallback posters', function () {
    $tmdb = app(Tmdb::class);

    expect($tmdb->imageUrl('https://static.tvmaze.com/bb.jpg'))
        ->toBe('https://static.tvmaze.com/bb.jpg');
});

test('home page stays available when tmdb is unreachable', function () {
    Http::fake([
        '*' => Http::failedConnection('Connection was reset'),
    ]);

    $this->get(route('home'))->assertOk();
});

test('tmdb relatedFromDetails prefers recommendations then newer similar titles', function () {
    $related = app(Tmdb::class)->relatedFromDetails([
        'recommendations' => [
            'results' => [
                ['id' => 10, 'title' => 'Recommended Recent', 'release_date' => '2023-01-01'],
                ['id' => 11, 'title' => 'Recommended Older', 'release_date' => '1999-01-01'],
            ],
        ],
        'similar' => [
            'results' => [
                ['id' => 20, 'title' => 'Similar Classic', 'release_date' => '1980-01-01'],
                ['id' => 10, 'title' => 'Duplicate Recommended', 'release_date' => '2023-01-01'],
                ['id' => 21, 'title' => 'Similar Newer', 'release_date' => '2024-06-01'],
            ],
        ],
    ], 4);

    expect($related)->toHaveCount(4)
        ->and($related[0]['id'])->toBe(10)
        ->and($related[1]['id'])->toBe(11)
        ->and($related[2]['id'])->toBe(21)
        ->and($related[3]['id'])->toBe(20);
});
