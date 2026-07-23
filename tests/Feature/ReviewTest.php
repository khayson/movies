<?php

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;

test('user can create a review', function () {
    $user = User::factory()->create();

    $review = $user->reviews()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Great movie',
        'rating' => 9,
        'body' => 'Loved every minute of it.',
        'contains_spoilers' => false,
    ]);

    expect($review)->toBeInstanceOf(Review::class);
    expect($review->rating)->toBe(9);
    expect($user->hasReviewed(550, 'movie'))->toBeTrue();
    expect($user->hasReviewed(551, 'movie'))->toBeFalse();
});

test('user can update their review via updateOrCreate', function () {
    $user = User::factory()->create();

    $user->reviews()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Good movie',
        'rating' => 7,
    ]);

    $user->reviews()->updateOrCreate(
        ['tmdb_id' => 550, 'media_type' => 'movie'],
        ['title' => 'Great movie', 'rating' => 9],
    );

    expect($user->reviews()->where('tmdb_id', 550)->count())->toBe(1);
    expect($user->reviews()->where('tmdb_id', 550)->first()->rating)->toBe(9);
});

test('user can delete their review', function () {
    $user = User::factory()->create();

    $user->reviews()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Good movie',
        'rating' => 7,
    ]);

    $user->reviews()->where('tmdb_id', 550)->where('media_type', 'movie')->delete();

    expect($user->hasReviewed(550, 'movie'))->toBeFalse();
});

test('review belongs to user', function () {
    $review = Review::factory()->create();

    expect($review->user)->toBeInstanceOf(User::class);
});

test('review factory creates valid records', function () {
    $review = Review::factory()->create();

    expect($review->tmdb_id)->toBeInt();
    expect($review->media_type)->toBeIn(['movie', 'tv']);
    expect($review->rating)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(10);
    expect($review->title)->toBeString();
});

test('review factory spoiler state works', function () {
    $review = Review::factory()->spoiler()->create();

    expect($review->contains_spoilers)->toBeTrue();
});

test('unique constraint prevents duplicate reviews per user per title', function () {
    $user = User::factory()->create();

    $user->reviews()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Good movie',
        'rating' => 7,
    ]);

    expect(fn () => $user->reviews()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Another review',
        'rating' => 5,
    ]))->toThrow(QueryException::class);
});

test('reviews are deleted when user is deleted', function () {
    $user = User::factory()->create();

    $user->reviews()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Good movie',
        'rating' => 7,
    ]);

    $user->delete();

    expect(Review::where('tmdb_id', 550)->count())->toBe(0);
});
