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
            'recommendations' => [
                'results' => [
                    ['id' => 807, 'title' => 'Se7en', 'poster_path' => '/se7en.jpg', 'release_date' => '1995-09-22', 'media_type' => 'movie'],
                    ['id' => 1399, 'name' => 'Game of Thrones', 'poster_path' => '/got.jpg', 'first_air_date' => '2011-04-17', 'media_type' => 'tv'],
                    ['id' => 1, 'title' => 'No Poster', 'poster_path' => null],
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

test('media card api returns links and more like this for the details modal', function () {
    $this->getJson(route('api.media.show', ['type' => 'movie', 'id' => 550]))
        ->assertOk()
        ->assertJsonPath('year', '1999')
        ->assertJsonPath('watch_url', route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertJsonPath('detail_url', route('movies.detail', 550))
        ->assertJsonCount(2, 'more_like_this')
        ->assertJsonPath('more_like_this.0.title', 'Se7en')
        ->assertJsonPath('more_like_this.1', [
            'id' => 1399,
            'type' => 'tv',
            'title' => 'Game of Thrones',
            'poster' => 'https://image.tmdb.org/t/p/w342/got.jpg',
            'year' => '2011',
        ]);
});

test('pages include the details modal and cards open it instead of navigating', function () {
    $this->get(route('movies.index'))
        ->assertOk()
        ->assertSee('role="dialog"', false)
        ->assertSee('@open-title.window', false);

    $html = view('partials.media-card', ['item' => ['id' => 550, 'title' => 'Fight Club', 'poster_path' => '/fc.jpg'], 'type' => 'movie'])->render();

    expect(baseCardLink($html))->toContain("\$dispatch('open-title', { type: 'movie', id: 550 })")
        ->and(baseCardLink($html))->not->toContain('wire:navigate');
});

test('continue watching cards still go straight to the player', function () {
    $user = User::factory()->create();
    $user->watchHistory()->create(['tmdb_id' => 550, 'media_type' => 'movie', 'title' => 'Fight Club', 'poster_path' => '/fc.jpg']);

    $link = baseCardLink(view('partials.media-card', ['item' => $user->watchHistory()->first(), 'variant' => 'watch'])->render());

    expect($link)->toContain('href="'.route('watch', ['type' => 'movie', 'tmdbId' => 550]).'"')
        ->and($link)->toContain('wire:navigate')
        ->and($link)->not->toContain('open-title');
});

function baseCardLink(string $html): string
{
    preg_match('/<a\s[^>]*class="block"[^>]*>/s', $html, $matches);

    return $matches[0] ?? '';
}

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
