<?php

use App\Support\UserPreferences;

test('defaults include advanced preference keys', function () {
    $defaults = UserPreferences::defaults();

    expect($defaults['remember_last_server'])->toBeTrue()
        ->and($defaults['auto_fallback_on_error'])->toBeTrue()
        ->and($defaults['prefer_hls_direct'])->toBeFalse()
        ->and($defaults['excluded_providers'])->toBe([])
        ->and($defaults['cinesrc_seek'])->toBe(10)
        ->and($defaults['cinesrc_back'])->toBe('close');
});

test('merge preserves unknown keys while applying updates', function () {
    $merged = UserPreferences::merge(
        ['custom_flag' => 'keep-me', 'preferred_type' => 'tv'],
        ['preferred_type' => 'movie', 'resume_prompt' => false],
    );

    expect($merged['custom_flag'])->toBe('keep-me')
        ->and($merged['preferred_type'])->toBe('movie')
        ->and($merged['resume_prompt'])->toBeFalse();
});

test('get falls back to documented defaults', function () {
    expect(UserPreferences::get(null, 'remember_last_server'))->toBeTrue()
        ->and(UserPreferences::get(['remember_last_server' => false], 'remember_last_server'))->toBeFalse()
        ->and(UserPreferences::get([], 'unknown_key', 'fallback'))->toBe('fallback');
});

test('bool casts preference values', function () {
    expect(UserPreferences::bool(null, 'auto_fallback_on_error'))->toBeTrue()
        ->and(UserPreferences::bool(['auto_fallback_on_error' => 0], 'auto_fallback_on_error'))->toBeFalse();
});
