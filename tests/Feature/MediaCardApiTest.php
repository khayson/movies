<?php

use App\Models\User;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'id' => 550,
            'title' => 'Fight Club',
            'name' => 'Fight Club',
            'overview' => 'An insomniac office worker...',
            'tagline' => 'Mischief. Mayhem. Soap.',
            'poster_path' => '/test.jpg',
            'backdrop_path' => '/backdrop.jpg',
            'vote_average' => 8.4,
            'vote_count' => 25000,
            'runtime' => 139,
            'status' => 'Released',
            'release_date' => '1999-10-15',
            'genres' => [
                ['id' => 18, 'name' => 'Drama'],
                ['id' => 53, 'name' => 'Thriller'],
            ],
            'credits' => [
                'cast' => [
                    ['name' => 'Brad Pitt', 'character' => 'Tyler Durden', 'profile_path' => '/brad.jpg'],
                    ['name' => 'Edward Norton', 'character' => 'Narrator', 'profile_path' => null],
                ],
            ],
            'videos' => [
                'results' => [
                    ['key' => 'SUXWAEX2jlg', 'site' => 'YouTube', 'type' => 'Trailer'],
                ],
            ],
        ]),
    ]);
});

test('media card api returns movie details', function () {
    $response = $this->getJson(route('api.media.show', ['type' => 'movie', 'id' => 550]));

    $response->assertOk()
        ->assertJsonFragment(['title' => 'Fight Club'])
        ->assertJsonFragment(['trailer_key' => 'SUXWAEX2jlg'])
        ->assertJsonStructure([
            'id', 'type', 'title', 'tagline', 'overview', 'rating',
            'runtime', 'genres', 'cast', 'trailer_key', 'backdrop',
            'is_favorited', 'is_watchlisted',
        ]);
});

test('media card api returns user watchlist and favorite status', function () {
    $user = User::factory()->create();

    Watchlist::factory()->create([
        'user_id' => $user->id,
        'tmdb_id' => 550,
        'media_type' => 'movie',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('api.media.show', ['type' => 'movie', 'id' => 550]));

    $response->assertOk()
        ->assertJsonFragment(['is_watchlisted' => true])
        ->assertJsonFragment(['is_favorited' => false]);
});

test('toggle watchlist adds and removes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.media.watchlist', ['type' => 'movie', 'id' => 550]));

    $response->assertOk()->assertJsonFragment(['added' => true]);
    expect($user->hasOnWatchlist(550, 'movie'))->toBeTrue();

    $response = $this->actingAs($user)
        ->postJson(route('api.media.watchlist', ['type' => 'movie', 'id' => 550]));

    $response->assertOk()->assertJsonFragment(['added' => false]);
    expect($user->hasOnWatchlist(550, 'movie'))->toBeFalse();
});

test('toggle favorite adds and removes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('api.media.favorite', ['type' => 'movie', 'id' => 550]));

    $response->assertOk()->assertJsonFragment(['added' => true]);
    expect($user->hasFavorited(550, 'movie'))->toBeTrue();

    $response = $this->actingAs($user)
        ->postJson(route('api.media.favorite', ['type' => 'movie', 'id' => 550]));

    $response->assertOk()->assertJsonFragment(['added' => false]);
    expect($user->hasFavorited(550, 'movie'))->toBeFalse();
});

test('toggle watchlist requires authentication', function () {
    $this->postJson(route('api.media.watchlist', ['type' => 'movie', 'id' => 550]))
        ->assertUnauthorized();
});

test('toggle favorite requires authentication', function () {
    $this->postJson(route('api.media.favorite', ['type' => 'movie', 'id' => 550]))
        ->assertUnauthorized();
});

test('media card api rejects invalid type', function () {
    $this->getJson('/api/media/invalid/550')
        ->assertNotFound();
});
