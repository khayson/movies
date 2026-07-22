<?php

use App\Models\Favorite;
use App\Models\User;

test('user can create a favorite', function () {
    $user = User::factory()->create();

    $favorite = $user->favorites()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
        'poster_path' => '/test.jpg',
    ]);

    expect($favorite)->toBeInstanceOf(Favorite::class);
    expect($user->hasFavorited(550, 'movie'))->toBeTrue();
    expect($user->hasFavorited(551, 'movie'))->toBeFalse();
});

test('user can remove a favorite', function () {
    $user = User::factory()->create();

    $user->favorites()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
        'poster_path' => '/test.jpg',
    ]);

    $user->favorites()->where('tmdb_id', 550)->where('media_type', 'movie')->delete();

    expect($user->hasFavorited(550, 'movie'))->toBeFalse();
});

test('favorite factory creates valid records', function () {
    $favorite = Favorite::factory()->create();

    expect($favorite->tmdb_id)->toBeInt();
    expect($favorite->media_type)->toBeIn(['movie', 'tv']);
    expect($favorite->title)->toBeString();
});
