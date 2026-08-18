<?php

use App\Services\AnimeDb;
use App\Services\Tmdb;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config(['services.rapidapi.key' => 'test-rapidapi-key']);
    config(['services.anime_db.host' => 'anime-db.p.rapidapi.com']);
    RateLimiter::clear('rapidapi');
    RateLimiter::clear('rapidapi-per-user');
});

test('anime db browse normalizes catalog results and meta', function () {
    Http::fake([
        'anime-db.p.rapidapi.com/anime*' => Http::response([
            'data' => [
                [
                    'id' => '5114',
                    'title' => 'Fullmetal Alchemist: Brotherhood',
                    'genres' => ['Action', 'Adventure'],
                    'image' => 'https://cdn.myanimelist.net/images/anime/1208/94745.jpg',
                    'link' => 'https://myanimelist.net/anime/5114/Fullmetal_Alchemist__Brotherhood',
                    'ranking' => 3,
                    'episodes' => 64,
                    'status' => 'Finished Airing',
                    'type' => 'TV',
                    'synopsis' => 'Two brothers search for a Philosopher\'s Stone.',
                ],
            ],
            'meta' => [
                'page' => 1,
                'size' => 10,
                'totalData' => 1,
                'totalPage' => 1,
            ],
        ]),
    ]);

    $result = app(AnimeDb::class)->browse(['page' => 1, 'size' => 10, 'search' => 'Fullmetal']);

    expect($result['data'])->toHaveCount(1)
        ->and($result['data'][0]['title'])->toBe('Fullmetal Alchemist: Brotherhood')
        ->and($result['data'][0]['ranking'])->toBe(3)
        ->and($result['meta']['totalData'])->toBe(1);
});

test('anime db find and byRanking hit their endpoints', function () {
    Http::fake([
        'anime-db.p.rapidapi.com/anime/by-id/5114' => Http::response([
            'id' => '5114',
            'title' => 'Fullmetal Alchemist: Brotherhood',
            'ranking' => 3,
            'type' => 'TV',
        ]),
        'anime-db.p.rapidapi.com/anime/by-ranking/1' => Http::response([
            'id' => '5114',
            'title' => 'Fullmetal Alchemist: Brotherhood',
            'ranking' => 1,
            'type' => 'TV',
        ]),
    ]);

    expect(app(AnimeDb::class)->find(5114)['title'])->toBe('Fullmetal Alchemist: Brotherhood')
        ->and(app(AnimeDb::class)->byRanking(1)['ranking'])->toBe(1);
});

test('anime db genres returns a string list', function () {
    Http::fake([
        'anime-db.p.rapidapi.com/genre' => Http::response(['Action', 'Drama', 'Fantasy']),
    ]);

    expect(app(AnimeDb::class)->genres())->toBe(['Action', 'Drama', 'Fantasy']);
});

test('anime db fails soft without a rapidapi key', function () {
    config(['services.rapidapi.key' => null]);

    Http::fake();

    expect(app(AnimeDb::class)->browse()['data'])->toBe([])
        ->and(app(AnimeDb::class)->find(5114))->toBeNull()
        ->and(app(AnimeDb::class)->genres())->toBe([]);

    Http::assertNothingSent();
});

test('anime db toTmdbCards resolves titles via tmdb search', function () {
    Http::fake([
        'api.themoviedb.org/3/search/multi*' => Http::response([
            'results' => [
                [
                    'id' => 31964,
                    'name' => 'Fullmetal Alchemist: Brotherhood',
                    'media_type' => 'tv',
                    'first_air_date' => '2009-04-05',
                    'poster_path' => '/fma.jpg',
                ],
            ],
        ]),
    ]);

    $cards = app(AnimeDb::class)->toTmdbCards([
        [
            'id' => '5114',
            'title' => 'Fullmetal Alchemist: Brotherhood',
            'ranking' => 3,
            'episodes' => 64,
            'type' => 'TV',
            'link' => 'https://myanimelist.net/anime/5114',
        ],
    ], app(Tmdb::class), 6);

    expect($cards)->toHaveCount(1)
        ->and($cards[0]['id'])->toBe(31964)
        ->and($cards[0]['anime_id'])->toBe('5114')
        ->and($cards[0]['anime_ranking'])->toBe(3);
});

test('anime index page renders without a rapidapi key', function () {
    config(['services.rapidapi.key' => null]);

    Http::fake([
        'api.themoviedb.org/3/discover/tv*' => Http::response([
            'results' => [
                [
                    'id' => 85937,
                    'name' => 'Demon Slayer',
                    'media_type' => 'tv',
                    'poster_path' => '/ds.jpg',
                    'backdrop_path' => '/ds-bg.jpg',
                    'overview' => 'Tanjiro quest.',
                ],
            ],
            'total_pages' => 1,
            'total_results' => 1,
        ]),
        '*' => Http::response(['results' => [], 'data' => [], 'genres' => []]),
    ]);

    $this->get(route('anime.index'))
        ->assertOk()
        ->assertSee('Anime Hub', false)
        ->assertSee('Demon Slayer', false);
});

test('tmdb animation discover uses the animation genre', function () {
    Http::fake([
        'api.themoviedb.org/3/discover/tv*' => Http::response([
            'results' => [
                ['id' => 1, 'name' => 'Animated Show'],
            ],
        ]),
    ]);

    $result = app(Tmdb::class)->animation('tv');

    expect($result['results'][0]['name'])->toBe('Animated Show');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'with_genres=16'));
});
