<?php

use App\Services\Tmdb;
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
