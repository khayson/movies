<?php

use App\Services\RottenTomatoes;
use App\Services\Tmdb;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config(['services.rapidapi.key' => 'test-rapidapi-key']);
    config(['services.rottentomatoes.host' => 'rottentomato.p.rapidapi.com']);
    RateLimiter::clear('rapidapi');
    RateLimiter::clear('rapidapi-per-user');
});

test('rotten tomatoes scores maps tomatometer audience and consensus', function () {
    Http::fake([
        'rottentomato.p.rapidapi.com/movies/by_name*' => Http::response([
            'title' => 'Inception',
            'tomatometer_score' => 87,
            'audience_score' => 91,
            'critics_consensus' => 'Smart, stylish and thrilling.',
            'year' => 2010,
        ]),
    ]);

    $scores = app(RottenTomatoes::class)->scores('Inception', 'movie', 2010);

    expect($scores)->toMatchArray([
        'tomatometer' => 87,
        'audience' => 91,
        'consensus' => 'Smart, stylish and thrilling.',
        'title' => 'Inception',
    ]);
});

test('rotten tomatoes scores uses the tv shows endpoint', function () {
    Http::fake([
        'rottentomato.p.rapidapi.com/tv_shows/by_name*' => Http::response([
            'title' => 'Breaking Bad',
            'tomatometer_score' => '96%',
            'audience_score' => '97%',
        ]),
    ]);

    $scores = app(RottenTomatoes::class)->scores('Breaking Bad', 'tv', 2008);

    expect($scores['tomatometer'])->toBe(96)
        ->and($scores['audience'])->toBe(97);
});

test('rotten tomatoes scores returns null without a rapidapi key', function () {
    config(['services.rapidapi.key' => null]);

    Http::fake();

    expect(app(RottenTomatoes::class)->scores('Inception'))->toBeNull();
    Http::assertNothingSent();
});

test('rotten tomatoes scores returns null for blank titles', function () {
    Http::fake();

    expect(app(RottenTomatoes::class)->scores(''))->toBeNull();
    expect(app(RottenTomatoes::class)->scores(null))->toBeNull();
    Http::assertNothingSent();
});

test('rotten tomatoes scores fails soft when the api errors', function () {
    Http::fake([
        'rottentomato.p.rapidapi.com/*' => Http::response(['message' => 'error'], 500),
    ]);

    expect(app(RottenTomatoes::class)->scores('Inception', 'movie'))->toBeNull();
});

test('rotten tomatoes scores prefers the year-matched candidate', function () {
    Http::fake([
        'rottentomato.p.rapidapi.com/movies/by_name*' => Http::response([
            'results' => [
                ['title' => 'Dune', 'year' => 1984, 'tomatometer_score' => 47, 'audience_score' => 65],
                ['title' => 'Dune', 'year' => 2021, 'tomatometer_score' => 83, 'audience_score' => 90],
            ],
        ]),
    ]);

    $scores = app(RottenTomatoes::class)->scores('Dune', 'movie', 2021);

    expect($scores['tomatometer'])->toBe(83)
        ->and($scores['audience'])->toBe(90);
});

test('rotten tomatoes netflix top tv normalizes list items', function () {
    Http::fake([
        'rottentomato.p.rapidapi.com/today-top100TVshows-netflix*' => Http::response([
            'shows' => [
                ['name' => 'Stranger Things', 'image' => 'https://example.com/st.jpg', 'tomatometer_score' => 93],
                ['title' => 'The Crown', 'audience_score' => '88%'],
            ],
        ]),
    ]);

    $items = app(RottenTomatoes::class)->netflixTopTv(10);

    expect($items)->toHaveCount(2)
        ->and($items[0]['title'])->toBe('Stranger Things')
        ->and($items[0]['type'])->toBe('tv')
        ->and($items[0]['tomatometer'])->toBe(93)
        ->and($items[1]['title'])->toBe('The Crown')
        ->and($items[1]['audience'])->toBe(88);
});

test('rotten tomatoes query movies and coming soon hit their endpoints', function () {
    Http::fake([
        'rottentomato.p.rapidapi.com/query_movies*' => Http::response([
            'movies' => [
                ['title' => 'Oppenheimer', 'year' => 2023, 'tomatometer_score' => 93],
            ],
        ]),
        'rottentomato.p.rapidapi.com/soon-in-theaters*' => Http::response([
            ['title' => 'Dune: Part Three', 'year' => 2026],
        ]),
    ]);

    $movies = app(RottenTomatoes::class)->queryMovies(1, 'popular', 5);
    $soon = app(RottenTomatoes::class)->comingSoon(5);

    expect($movies)->toHaveCount(1)
        ->and($movies[0]['title'])->toBe('Oppenheimer')
        ->and($soon)->toHaveCount(1)
        ->and($soon[0]['title'])->toBe('Dune: Part Three');
});

test('rotten tomatoes toTmdbCards resolves titles via tmdb search', function () {
    Http::fake([
        'api.themoviedb.org/3/search/multi*' => Http::response([
            'results' => [
                [
                    'id' => 1396,
                    'name' => 'Breaking Bad',
                    'media_type' => 'tv',
                    'first_air_date' => '2008-01-20',
                    'poster_path' => '/bb.jpg',
                ],
            ],
        ]),
    ]);

    $cards = app(RottenTomatoes::class)->toTmdbCards([
        [
            'title' => 'Breaking Bad',
            'image' => null,
            'tomatometer' => 96,
            'audience' => 97,
            'year' => 2008,
            'type' => 'tv',
        ],
    ], app(Tmdb::class), 6);

    expect($cards)->toHaveCount(1)
        ->and($cards[0]['id'])->toBe(1396)
        ->and($cards[0]['rt_tomatometer'])->toBe(96);
});

test('rotten tomatoes list methods return empty arrays without a key', function () {
    config(['services.rapidapi.key' => null]);

    Http::fake();

    expect(app(RottenTomatoes::class)->netflixTopTv())->toBe([])
        ->and(app(RottenTomatoes::class)->queryMovies())->toBe([])
        ->and(app(RottenTomatoes::class)->comingSoon())->toBe([]);

    Http::assertNothingSent();
});
