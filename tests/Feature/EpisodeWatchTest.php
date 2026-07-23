<?php

use App\Models\EpisodeWatch;
use App\Models\User;
use Illuminate\Database\QueryException;

test('user can mark episode as watched', function () {
    $user = User::factory()->create();

    $watch = $user->episodeWatches()->create([
        'tmdb_id' => 1399,
        'season_number' => 1,
        'episode_number' => 1,
    ]);

    expect($watch)->toBeInstanceOf(EpisodeWatch::class);
    expect($user->episodeWatches()->count())->toBe(1);
});

test('user can unmark episode as watched', function () {
    $user = User::factory()->create();

    $user->episodeWatches()->create([
        'tmdb_id' => 1399,
        'season_number' => 1,
        'episode_number' => 1,
    ]);

    $user->episodeWatches()
        ->where('tmdb_id', 1399)
        ->where('season_number', 1)
        ->where('episode_number', 1)
        ->delete();

    expect($user->episodeWatches()->count())->toBe(0);
});

test('unique constraint prevents duplicate episode watches', function () {
    $user = User::factory()->create();

    $user->episodeWatches()->create([
        'tmdb_id' => 1399,
        'season_number' => 1,
        'episode_number' => 1,
    ]);

    expect(fn () => $user->episodeWatches()->create([
        'tmdb_id' => 1399,
        'season_number' => 1,
        'episode_number' => 1,
    ]))->toThrow(QueryException::class);
});

test('episode watches are deleted when user is deleted', function () {
    $user = User::factory()->create();

    $user->episodeWatches()->create([
        'tmdb_id' => 1399,
        'season_number' => 1,
        'episode_number' => 1,
    ]);

    $user->delete();

    expect(EpisodeWatch::where('tmdb_id', 1399)->count())->toBe(0);
});

test('factory creates valid records', function () {
    $watch = EpisodeWatch::factory()->create();

    expect($watch->tmdb_id)->toBeInt();
    expect($watch->season_number)->toBeGreaterThanOrEqual(1);
    expect($watch->episode_number)->toBeGreaterThanOrEqual(1);
    expect($watch->watched_at)->toBeInstanceOf(DateTimeInterface::class);
});
