<?php

use App\Support\AdultSafety;
use Tests\TestCase;

uses(TestCase::class);

test('allows ordinary adult search terms', function () {
    expect(AdultSafety::isBlockedQuery(''))->toBeFalse()
        ->and(AdultSafety::isBlockedQuery('amateur'))->toBeFalse()
        ->and(AdultSafety::isBlockedQuery('eighteen'))->toBeFalse();
});

test('blocks minor-related queries', function (string $query) {
    expect(AdultSafety::isBlockedQuery($query))->toBeTrue();
})->with([
    'teen',
    'teens',
    'teenager',
    'preteen',
    'child',
    'kids',
    'underage',
    'loli',
]);

test('rejects catalog rows with blocked titles', function () {
    $videos = AdultSafety::rejectBlockedTitles([
        ['title' => 'Studio Cut'],
        ['title' => 'Teen Something'],
        ['title' => 'Amateur Night'],
    ]);

    expect($videos)->toHaveCount(2)
        ->and(collect($videos)->pluck('title')->all())->toBe(['Studio Cut', 'Amateur Night']);
});
