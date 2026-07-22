<?php

use Illuminate\Support\Facades\Http;

test('homepage returns a successful response', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [
                [
                    'id' => 550,
                    'title' => 'Fight Club',
                    'media_type' => 'movie',
                    'overview' => 'A test movie',
                    'backdrop_path' => '/test.jpg',
                    'poster_path' => '/test.jpg',
                    'vote_average' => 8.4,
                    'release_date' => '1999-10-15',
                ],
            ],
            'total_pages' => 1,
        ]),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk();
});
