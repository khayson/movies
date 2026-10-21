<?php

use App\Models\User;
use App\Models\WatchHistory;
use App\Services\TopTen;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/3/movie/100*' => Http::response(['id' => 100, 'title' => 'Most Watched', 'poster_path' => '/100.jpg', 'adult' => false]),
        'api.themoviedb.org/3/movie/200*' => Http::response(['id' => 200, 'title' => 'Second Place', 'poster_path' => '/200.jpg', 'adult' => false]),
        'api.themoviedb.org/3/movie/300*' => Http::response(['id' => 300, 'title' => 'Adult Title', 'poster_path' => '/300.jpg', 'adult' => true]),
        'api.themoviedb.org/3/trending/movie/week*' => Http::response(['results' => [
            ['id' => 200, 'title' => 'Second Place', 'poster_path' => '/200.jpg'],
            ['id' => 900, 'title' => 'Trending Fill', 'poster_path' => '/900.jpg'],
        ]]),
        '*' => Http::response(['results' => []]),
    ]);
});

function watchedBy(int $viewers, int $tmdbId, bool $private = false): void
{
    foreach (range(1, $viewers) as $ignored) {
        User::factory()->create()->watchHistory()->create([
            'tmdb_id' => $tmdbId,
            'media_type' => 'movie',
            'title' => "Title {$tmdbId}",
            'is_private' => $private,
        ]);
    }
}

test('titles watched by the most accounts rank first and trending fills the rest', function () {
    watchedBy(3, 100);
    watchedBy(2, 200);

    $ids = array_column(app(TopTen::class)->forType('movie'), 'id');

    expect($ids)->toBe([100, 200, 900]);
});

test('a single viewer, private viewing and adult titles never reach the public top 10', function () {
    watchedBy(1, 100);
    watchedBy(5, 200, private: true);
    watchedBy(4, 300);

    $ids = array_column(app(TopTen::class)->forType('movie'), 'id');

    expect($ids)->toBe([200, 900])
        ->and($ids)->not->toContain(300);
});

test('views older than a week no longer count', function () {
    watchedBy(3, 100);
    WatchHistory::query()->update(['updated_at' => now()->subDays(8)]);

    expect(array_column(app(TopTen::class)->forType('movie'), 'id'))->toBe([200, 900]);
});
