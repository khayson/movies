<?php

use App\Support\SettingsSearch;
use Tests\TestCase;

uses(TestCase::class);

test('catalog includes core settings destinations', function () {
    $ids = collect(SettingsSearch::catalog())->pluck('id');

    expect($ids)->toContain('profile', 'pref-streaming', 'pref-advanced', 'security-2fa', 'appearance');
});

test('search finds settings by phrase intent', function () {
    $results = SettingsSearch::search('auto switch server');

    expect($results)->not->toBeEmpty()
        ->and($results[0]['id'])->toBe('pref-fallback')
        ->and($results[0]['anchor'])->toBe('settings-advanced')
        ->and($results[0]['url'])->toContain('#settings-advanced');
});

test('search finds two factor with common shorthand', function () {
    $results = SettingsSearch::search('2fa');

    expect($results)->not->toBeEmpty()
        ->and($results[0]['id'])->toBe('security-2fa');
});

test('search finds hls preference', function () {
    $results = SettingsSearch::search('hls direct');

    expect(collect($results)->pluck('id'))->toContain('pref-hls');
});

test('search returns empty for blank query', function () {
    expect(SettingsSearch::search('   '))->toBe([]);
});

test('client index exposes haystack and url', function () {
    $item = SettingsSearch::clientIndex()[0];

    expect($item)->toHaveKeys(['id', 'label', 'description', 'group', 'url', 'anchor', 'haystack'])
        ->and($item['haystack'])->not->toBeEmpty()
        ->and($item['url'])->toContain('#');
});
