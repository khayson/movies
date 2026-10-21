<?php

use App\Services\Tmdb;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 21));
});

test('popular movies only include releases from the last two years', function () {
    Http::fake(['*' => Http::response(['results' => [['id' => 1, 'title' => 'New Movie']], 'total_pages' => 1])]);

    app(Tmdb::class)->popular('movie');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/discover/movie')
        && $request['sort_by'] === 'popularity.desc'
        && $request['primary_release_date.gte'] === '2024-10-21'
        && $request['primary_release_date.lte'] === '2026-10-21');
});

test('tv lists only include shows that first aired in the last two years', function () {
    Http::fake(['*' => Http::response(['results' => [['id' => 1, 'name' => 'New Season']], 'total_pages' => 1])]);

    app(Tmdb::class)->topRated('tv');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/discover/tv')
        && $request['sort_by'] === 'vote_average.desc'
        && (int) $request['vote_count.gte'] === 100
        && $request['first_air_date.gte'] === '2024-10-21');
});

test('anime is recent japanese animation', function () {
    Http::fake(['*' => Http::response(['results' => [['id' => 1, 'name' => 'New Anime']], 'total_pages' => 1])]);

    app(Tmdb::class)->anime('tv');

    Http::assertSent(fn (Request $request): bool => (int) $request['with_genres'] === Tmdb::ANIMATION_GENRE_ID
        && $request['with_origin_country'] === 'JP'
        && isset($request['first_air_date.gte']));
});

test('small catalogues with nothing recent fall back to all-time results', function () {
    Http::fake(function (Request $request) {
        return isset($request['primary_release_date.gte'])
            ? Http::response(['results' => [], 'total_pages' => 0])
            : Http::response(['results' => [['id' => 7, 'title' => 'Older Classic']], 'total_pages' => 1]);
    });

    $result = app(Tmdb::class)->discoverByOriginCountry('movie', 'GH');

    expect($result['results'][0]['title'])->toBe('Older Classic');
});
