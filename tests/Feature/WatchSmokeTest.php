<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [
                [
                    'id' => 550,
                    'media_type' => 'movie',
                    'title' => 'Fight Club',
                    'poster_path' => '/test.jpg',
                    'vote_average' => 8.4,
                    'release_date' => '1999-10-15',
                ],
            ],
            'total_pages' => 1,
            'id' => 550,
            'title' => 'Fight Club',
            'overview' => 'A test movie',
            'poster_path' => '/test.jpg',
            'backdrop_path' => '/test.jpg',
            'vote_average' => 8.4,
            'release_date' => '1999-10-15',
            'runtime' => 139,
            'status' => 'Released',
            'genres' => [['id' => 18, 'name' => 'Drama']],
            'credits' => ['cast' => []],
            'videos' => ['results' => []],
            'similar' => ['results' => []],
            'recommendations' => ['results' => []],
        ]),
        'ai-movie-recommender.p.rapidapi.com/*' => Http::response([
            'success' => true,
            'movies' => [],
        ]),
    ]);
});

test('core pages load without server errors', function (string $uri) {
    $this->get($uri)->assertSuccessful();
})->with([
    'home' => ['/'],
    'search' => ['/search'],
    'search ai' => ['/search?mode=ai&q=action'],
    'mood' => ['/mood'],
    'watch parties' => ['/watch-parties'],
    'architecture' => ['/architecture'],
    'watch movie' => ['/watch/movie/550'],
]);

test('watch page smoke includes player fallback and disclaimer', function () {
    $this->get(route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertSuccessful()
        ->assertSee('Disclaimer')
        ->assertSee('bindPlayerMessages', false)
        ->assertSee('reportServerError', false);
});
