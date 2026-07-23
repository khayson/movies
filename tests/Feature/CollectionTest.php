<?php

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\User;
use Illuminate\Database\QueryException;

test('user can create a collection', function () {
    $user = User::factory()->create();

    $collection = $user->collections()->create([
        'name' => 'Best Sci-Fi',
        'description' => 'My top sci-fi picks',
        'is_public' => true,
        'slug' => 'best-sci-fi-abc123',
    ]);

    expect($collection)->toBeInstanceOf(Collection::class);
    expect($collection->name)->toBe('Best Sci-Fi');
    expect($user->collections()->count())->toBe(1);
});

test('collection can have items', function () {
    $collection = Collection::factory()->create();

    $item = $collection->items()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
        'poster_path' => '/test.jpg',
        'sort_order' => 0,
    ]);

    expect($item)->toBeInstanceOf(CollectionItem::class);
    expect($collection->items()->count())->toBe(1);
});

test('collection items have unique constraint per collection', function () {
    $collection = Collection::factory()->create();

    $collection->items()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
        'sort_order' => 0,
    ]);

    expect(fn () => $collection->items()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club Again',
        'sort_order' => 1,
    ]))->toThrow(QueryException::class);
});

test('same title can be in different collections', function () {
    $user = User::factory()->create();
    $col1 = Collection::factory()->for($user)->create();
    $col2 = Collection::factory()->for($user)->create();

    $col1->items()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
        'sort_order' => 0,
    ]);

    $col2->items()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
        'sort_order' => 0,
    ]);

    expect($col1->items()->count())->toBe(1);
    expect($col2->items()->count())->toBe(1);
});

test('deleting collection cascades to items', function () {
    $collection = Collection::factory()->create();
    $collection->items()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
        'sort_order' => 0,
    ]);

    $collection->delete();

    expect(CollectionItem::where('tmdb_id', 550)->count())->toBe(0);
});

test('deleting user cascades to collections', function () {
    $user = User::factory()->create();
    $collection = Collection::factory()->for($user)->create();
    $collection->items()->create([
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'title' => 'Fight Club',
        'sort_order' => 0,
    ]);

    $user->delete();

    expect(Collection::where('id', $collection->id)->count())->toBe(0);
    expect(CollectionItem::where('collection_id', $collection->id)->count())->toBe(0);
});

test('collection factory creates valid records', function () {
    $collection = Collection::factory()->create();

    expect($collection->name)->toBeString();
    expect($collection->slug)->toBeString();
    expect($collection->is_public)->toBeBool();
});

test('collection item factory creates valid records', function () {
    $item = CollectionItem::factory()->create();

    expect($item->tmdb_id)->toBeInt();
    expect($item->media_type)->toBeIn(['movie', 'tv']);
    expect($item->title)->toBeString();
});

test('collection items are ordered by sort_order', function () {
    $collection = Collection::factory()->create();

    $collection->items()->create(['tmdb_id' => 1, 'media_type' => 'movie', 'title' => 'Third', 'sort_order' => 2]);
    $collection->items()->create(['tmdb_id' => 2, 'media_type' => 'movie', 'title' => 'First', 'sort_order' => 0]);
    $collection->items()->create(['tmdb_id' => 3, 'media_type' => 'movie', 'title' => 'Second', 'sort_order' => 1]);

    $items = $collection->items()->get();

    expect($items[0]->title)->toBe('First');
    expect($items[1]->title)->toBe('Second');
    expect($items[2]->title)->toBe('Third');
});
