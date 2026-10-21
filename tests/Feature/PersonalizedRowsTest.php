<?php

use App\Models\User;
use App\Services\PersonalizedRows;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/3/movie/550*' => Http::response([
            'id' => 550,
            'title' => 'Fight Club',
            'genres' => [['id' => 18, 'name' => 'Drama']],
            'recommendations' => ['results' => [
                ['id' => 807, 'title' => 'Se7en', 'poster_path' => '/se7en.jpg', 'media_type' => 'movie'],
                ['id' => 680, 'title' => 'Pulp Fiction', 'poster_path' => '/pulp.jpg', 'media_type' => 'movie'],
                ['id' => 999, 'title' => 'No Poster', 'poster_path' => null, 'media_type' => 'movie'],
            ]],
            'similar' => ['results' => []],
        ]),
        'api.themoviedb.org/3/movie/680*' => Http::response(['id' => 680, 'title' => 'Pulp Fiction', 'genres' => [['id' => 80, 'name' => 'Crime']]]),
        'api.themoviedb.org/3/movie/404404*' => Http::response(['status_message' => 'Not found'], 404),
        'api.themoviedb.org/3/discover/movie*' => Http::response(['results' => [
            ['id' => 13, 'title' => 'Forrest Gump', 'poster_path' => '/gump.jpg'],
            ['id' => 550, 'title' => 'Fight Club', 'poster_path' => '/fc.jpg'],
        ]]),
        '*' => Http::response(['results' => [], 'total_pages' => 1]),
    ]);
});

function watch(User $user, int $tmdbId, string $title): void
{
    $user->watchHistory()->create(['tmdb_id' => $tmdbId, 'media_type' => 'movie', 'title' => $title]);
}

test('rows are built from the profile watch history without already watched titles', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    watch($user, 680, 'Pulp Fiction');
    watch($user, 550, 'Fight Club');

    $rows = app(PersonalizedRows::class)->forUser($user);
    $titles = array_column($rows, 'title');

    expect($titles)->toContain('Because you watched Fight Club')
        ->and($titles)->toContain('More Drama for you');

    $becauseFightClub = collect($rows)->firstWhere('title', 'Because you watched Fight Club');
    expect(array_column($becauseFightClub['items'], 'id'))->toBe([807]);

    $moreDrama = collect($rows)->firstWhere('title', 'More Drama for you');
    expect(array_column($moreDrama['items'], 'id'))->toBe([13]);
});

test('a profile without history gets no personalized rows', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    watch($user, 550, 'Fight Club');

    $user->switchProfile($user->profiles()->create(['name' => 'Kids']));

    expect(app(PersonalizedRows::class)->forUser($user))->toBe([]);
});

test('titles that tmdb no longer knows are skipped instead of breaking the page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    watch($user, 404404, 'Removed Title');

    expect(app(PersonalizedRows::class)->forUser($user))->toBe([]);
    $this->get(route('home'))->assertOk();
});

test('the home page shows personalized rows to signed-in viewers', function () {
    $user = User::factory()->create();
    watch($user, 550, 'Fight Club');

    $this->actingAs($user)->get(route('home'))
        ->assertOk()
        ->assertSee('Because you watched Fight Club')
        ->assertSee('More Drama for you');

    auth()->logout();

    $this->get(route('home'))->assertOk()->assertDontSee('Because you watched');
});
