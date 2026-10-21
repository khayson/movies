<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/3/trending/movie/day*' => Http::response([
            'results' => [
                ['id' => 101, 'title' => 'Daily Movie One', 'poster_path' => '/m1.jpg', 'media_type' => 'movie'],
                ['id' => 102, 'title' => 'Daily Movie Two', 'poster_path' => '/m2.jpg', 'media_type' => 'movie'],
            ],
        ]),
        'api.themoviedb.org/3/trending/tv/day*' => Http::response([
            'results' => [
                ['id' => 201, 'name' => 'Daily Show One', 'poster_path' => '/t1.jpg', 'media_type' => 'tv'],
            ],
        ]),
        'api.themoviedb.org/3/*/videos*' => Http::response([
            'results' => [['key' => 'abc123XYZ', 'site' => 'YouTube', 'type' => 'Trailer', 'official' => true]],
        ]),
        'api.themoviedb.org/*' => Http::response([
            'results' => [[
                'id' => 550,
                'title' => 'Fight Club',
                'media_type' => 'movie',
                'overview' => 'A test movie',
                'backdrop_path' => '/test.jpg',
                'poster_path' => '/test.jpg',
                'vote_average' => 8.4,
                'release_date' => '1999-10-15',
            ]],
            'total_pages' => 1,
        ]),
        '*' => Http::response([]),
    ]);
});

test('home page shows ranked top 10 rows for movies and shows', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Top 10 Movies Today', '#1 Daily Movie One', '#2 Daily Movie Two', 'Top 10 Shows Today', '#1 Daily Show One'])
        ->assertSee(route('movies.detail', 101))
        ->assertSee(route('tv.detail', 201));
});

test('home billboard autoplays a muted trailer with a sound toggle', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('abc123XYZ')
        ->assertSee('youtube-nocookie.com/embed/', false)
        ->assertSee('Unmute trailer')
        ->assertSee('prefers-reduced-motion', false);
});
