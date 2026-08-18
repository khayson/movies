<?php

use App\Services\TvMaze;
use Illuminate\Support\Facades\Http;

test('tvmaze catalog maps shows that have tmdb ids', function () {
    Http::fake([
        'api.tvmaze.com/shows*' => Http::response([
            [
                'name' => 'Breaking Bad',
                'summary' => '<p>A chemistry teacher.</p>',
                'premiered' => '2008-01-20',
                'image' => ['medium' => 'https://static.tvmaze.com/bb.jpg'],
                'rating' => ['average' => 9.3],
                'externals' => ['tmdb' => 1396],
            ],
            [
                'name' => 'Unknown Show',
                'externals' => ['imdb' => 'tt000'],
            ],
        ]),
    ]);

    $shows = app(TvMaze::class)->catalog(10);

    expect($shows)->toHaveCount(1)
        ->and($shows[0]['id'])->toBe(1396)
        ->and($shows[0]['media_type'])->toBe('tv')
        ->and($shows[0]['overview'])->toBe('A chemistry teacher.');
});

test('tvmaze search and lookup fail soft when the api is down', function () {
    Http::fake([
        'api.tvmaze.com/*' => Http::failedConnection('Connection was reset'),
    ]);

    expect(app(TvMaze::class)->search('lost'))->toBe([])
        ->and(app(TvMaze::class)->showByTmdbId(1396))->toBeNull();
});
