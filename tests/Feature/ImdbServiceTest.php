<?php

use App\Services\Imdb;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config(['services.rapidapi.key' => 'test-rapidapi-key']);
    config(['services.imdb.host' => 'imdb236.p.rapidapi.com']);
    RateLimiter::clear('rapidapi');
    RateLimiter::clear('rapidapi-per-user');
});

test('imdb ratings merges score votes and metascore', function () {
    Http::fake([
        'imdb236.p.rapidapi.com/api/imdb/tt0133093/rating' => Http::response([
            'averageRating' => 8.7,
            'numVotes' => 2100000,
        ]),
        'imdb236.p.rapidapi.com/api/imdb/tt0133093/metascore' => Http::response([
            'metascore' => 73,
        ]),
    ]);

    $ratings = app(Imdb::class)->ratings('tt0133093');

    expect($ratings)->toBe([
        'rating' => 8.7,
        'votes' => 2100000,
        'metascore' => 73,
    ]);

    Http::assertSentCount(2);
});

test('imdb ratings returns null without a rapidapi key', function () {
    config(['services.rapidapi.key' => null]);

    Http::fake();

    expect(app(Imdb::class)->ratings('tt0133093'))->toBeNull();
    Http::assertNothingSent();
});

test('imdb ratings returns null for invalid title ids', function () {
    Http::fake();

    expect(app(Imdb::class)->ratings('nm0000001'))->toBeNull();
    expect(app(Imdb::class)->ratings(''))->toBeNull();
    expect(app(Imdb::class)->ratings(null))->toBeNull();
    Http::assertNothingSent();
});

test('imdb ratings fails soft when the api errors', function () {
    Http::fake([
        'imdb236.p.rapidapi.com/*' => Http::response(['message' => 'error'], 500),
    ]);

    expect(app(Imdb::class)->ratings('tt0133093'))->toBeNull();
});

test('imdb cast titles normalizes nested title lists', function () {
    Http::fake([
        'imdb236.p.rapidapi.com/api/imdb/cast/nm0000190/titles' => Http::response([
            'titles' => [
                ['id' => 'tt0133093', 'primaryTitle' => 'The Matrix', 'type' => 'movie'],
                ['id' => 'tt0234215', 'primaryTitle' => 'The Matrix Reloaded', 'type' => 'movie'],
            ],
        ]),
    ]);

    $titles = app(Imdb::class)->castTitles('nm0000190', 1);

    expect($titles)->toHaveCount(1)
        ->and($titles[0]['primaryTitle'])->toBe('The Matrix');
});

test('imdb similar returns an empty list when the response is empty', function () {
    Http::fake([
        'imdb236.p.rapidapi.com/api/imdb/tt0133093/similar' => Http::response([]),
    ]);

    expect(app(Imdb::class)->similar('tt0133093'))->toBe([]);
});
