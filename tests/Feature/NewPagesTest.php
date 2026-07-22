<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [
                [
                    'id' => 550,
                    'title' => 'Fight Club',
                    'name' => 'Fight Club',
                    'media_type' => 'movie',
                    'overview' => 'A test movie',
                    'backdrop_path' => '/test.jpg',
                    'poster_path' => '/test.jpg',
                    'vote_average' => 8.4,
                    'release_date' => '1999-10-15',
                ],
            ],
            'genres' => [
                ['id' => 28, 'name' => 'Action'],
                ['id' => 12, 'name' => 'Adventure'],
            ],
            'total_pages' => 1,
        ]),
    ]);
});

test('genres page loads successfully', function () {
    $this->get(route('genres.index'))->assertOk();
});

test('genre browse page loads successfully', function () {
    $this->get(route('genres.browse', [
        'type' => 'movie',
        'genreId' => 28,
        'genreName' => 'action',
    ]))->assertOk();
});

test('upcoming page loads successfully', function () {
    $this->get(route('upcoming.index'))->assertOk();
});

test('new releases page loads successfully', function () {
    $this->get(route('new-releases'))->assertOk();
});

test('search page shows trending when no query', function () {
    $this->get(route('search'))->assertOk();
});
