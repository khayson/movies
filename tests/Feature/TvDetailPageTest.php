<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

function tvDetailPayload(): array
{
    return [
        'id' => 1396,
        'name' => 'Breaking Bad',
        'tagline' => 'Change the equation.',
        'overview' => 'A chemistry teacher turned meth cook.',
        'poster_path' => '/bb.jpg',
        'backdrop_path' => '/bb-bg.jpg',
        'vote_average' => 8.9,
        'vote_count' => 12000,
        'first_air_date' => '2008-01-20',
        'last_air_date' => '2013-09-29',
        'number_of_seasons' => 5,
        'number_of_episodes' => 62,
        'status' => 'Ended',
        'genres' => [['id' => 18, 'name' => 'Drama']],
        'networks' => [['id' => 174, 'name' => 'AMC']],
        'created_by' => [['id' => 66633, 'name' => 'Vince Gilligan']],
        'credits' => [
            'cast' => [
                ['id' => 17419, 'name' => 'Bryan Cranston', 'character' => 'Walter White', 'profile_path' => '/bc.jpg'],
            ],
        ],
        'videos' => ['results' => []],
        'similar' => ['results' => []],
        'recommendations' => ['results' => []],
        'reviews' => ['results' => []],
        'external_ids' => ['imdb_id' => 'tt0903747'],
        'seasons' => [
            ['season_number' => 1, 'episode_count' => 7, 'name' => 'Season 1'],
        ],
    ];
}

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/3/tv/1396/season/*' => Http::response([
            'episodes' => [
                [
                    'episode_number' => 1,
                    'name' => 'Pilot',
                    'overview' => 'Walter White starts cooking.',
                    'runtime' => 58,
                    'still_path' => '/pilot.jpg',
                    'air_date' => '2008-01-20',
                ],
                [
                    'episode_number' => 2,
                    'name' => 'Cats in the Bag',
                    'overview' => 'Walt and Jesse clean up.',
                    'runtime' => 48,
                    'still_path' => '/ep2.jpg',
                    'air_date' => '2008-01-27',
                ],
            ],
        ]),
        'api.themoviedb.org/*' => Http::response(tvDetailPayload()),
        'api.tmdb.org/3/tv/1396/season/*' => Http::response([
            'episodes' => [
                [
                    'episode_number' => 1,
                    'name' => 'Pilot',
                    'overview' => 'Walter White starts cooking.',
                    'runtime' => 58,
                    'still_path' => '/pilot.jpg',
                    'air_date' => '2008-01-20',
                ],
                [
                    'episode_number' => 2,
                    'name' => 'Cats in the Bag',
                    'overview' => 'Walt and Jesse clean up.',
                    'runtime' => 48,
                    'still_path' => '/ep2.jpg',
                    'air_date' => '2008-01-27',
                ],
            ],
        ]),
        'api.tmdb.org/*' => Http::response(tvDetailPayload()),
        '*' => Http::response(['results' => []], 200),
    ]);
});

test('tv detail page uses the series hero and episode layout', function () {
    $this->get(route('tv.detail', 1396))
        ->assertOk()
        ->assertSee('Breaking Bad')
        ->assertSee('Series')
        ->assertSee('Vince Gilligan')
        ->assertSee('AMC')
        ->assertSee('Watch S1 E1')
        ->assertSee('Episodes')
        ->assertSee('Pilot')
        ->assertSee('Cast')
        ->assertSee('Watchlist');
});

test('authenticated users can continue a series from the tv detail page', function () {
    $user = User::factory()->create();
    $user->episodeWatches()->create([
        'tmdb_id' => 1396,
        'season_number' => 1,
        'episode_number' => 1,
        'watched_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('tv.detail', 1396))
        ->assertOk()
        ->assertSee('Continue watching')
        ->assertSee('Your progress');
});
