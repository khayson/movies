<?php

use Illuminate\Support\Facades\Http;

test('home page is a single stack of rows with explore links and genre shortcuts', function () {
    Http::fake([
        'api.themoviedb.org/3/genre/movie/list*' => Http::response(['genres' => [['id' => 28, 'name' => 'Action']]]),
        'api.themoviedb.org/*' => Http::response(['results' => [
            ['id' => 550, 'title' => 'Fight Club', 'poster_path' => '/fc.jpg', 'media_type' => 'movie', 'release_date' => '2025-01-01'],
        ]]),
        '*' => Http::response(['results' => []]),
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Trending Now', 'Popular Movies', 'Popular Series', 'In Cinemas Now', 'Critically Acclaimed Movies', 'Browse by genre'])
        ->assertSee(route('movies.index'), false)
        ->assertSee(route('genres.browse', ['type' => 'movie', 'genreId' => 28, 'genreName' => 'action']), false)
        ->assertDontSee('Anime Hub');
});
