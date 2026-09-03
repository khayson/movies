<?php

use App\Services\EmbedUrlBuilder;
use Tests\TestCase;

uses(TestCase::class);

test('enriches vidcore urls with autoplay resume and theme', function () {
    $url = app(EmbedUrlBuilder::class)->enrich(
        'https://vidcore.org/embed/movie/550',
        [
            'embed_options' => [
                'autoplay' => 'autoPlay',
                'autoplay_format' => 'true_false',
                'progress' => 'startAt',
                'theme' => 'theme',
                'theme_value' => 'd97706',
            ],
        ],
        [
            'autoplay' => true,
            'progress_seconds' => 120,
        ],
    );

    expect($url)->toContain('autoPlay=true')
        ->and($url)->toContain('startAt=120')
        ->and($url)->toContain('theme=d97706');
});

test('preserves existing query string when enriching', function () {
    $url = app(EmbedUrlBuilder::class)->enrich(
        'https://multiembed.mov/?video_id=550&tmdb=1',
        [
            'embed_options' => [
                'autoplay' => 'autoplay',
                'autoplay_format' => 'one_zero',
            ],
        ],
        ['autoplay' => true],
    );

    expect($url)->toContain('video_id=550')
        ->and($url)->toContain('tmdb=1')
        ->and($url)->toContain('autoplay=1');
});

test('skips progress below the resume threshold', function () {
    $url = app(EmbedUrlBuilder::class)->enrich(
        'https://vidsrc.mov/embed/movie/550',
        ['embed_options' => ['progress' => 't']],
        ['progress_seconds' => 10],
    );

    expect($url)->not->toContain('t=');
});
