<?php

use App\Services\CineSrcEmbed;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'sources.cinesrc.base_url' => 'https://cinesrc.st',
        'sources.cinesrc.color' => '#d97706',
        'sources.cinesrc.seek' => 10,
        'sources.cinesrc.autoplay' => true,
        'sources.cinesrc.autonext' => true,
        'sources.cinesrc.autoskip' => false,
        'sources.cinesrc.back' => 'close',
    ]);
});

test('builds movie embed url with defaults', function () {
    $url = app(CineSrcEmbed::class)->buildUrl(1084242, 'movie');

    expect($url)->toStartWith('https://cinesrc.st/embed/movie/1084242?')
        ->and($url)->toContain('color=%23d97706')
        ->and($url)->toContain('seek=10')
        ->and($url)->toContain('autoplay=true')
        ->and($url)->toContain('back=close');
});

test('builds tv embed url with season and episode', function () {
    $url = app(CineSrcEmbed::class)->buildUrl(1396, 'tv', 2, 5);

    expect($url)->toContain('/embed/tv/1396?')
        ->and($url)->toContain('s=2')
        ->and($url)->toContain('e=5')
        ->and($url)->toContain('autonext=true');
});

test('includes resume and server preference parameters', function () {
    $url = app(CineSrcEmbed::class)->buildUrl(1084242, 'movie', null, null, [
        'progress_seconds' => 120,
        'cinesrc_server_id' => 'feb-1080',
        'quality' => '1080',
        'continue_prompt' => true,
    ]);

    expect($url)->toContain('t=120')
        ->and($url)->toContain('continueprompt=true')
        ->and($url)->toContain('lastserver=feb-1080')
        ->and($url)->toContain('prioritize=true')
        ->and($url)->toContain('quality=1080');
});

test('includes autoskip for tv when enabled', function () {
    $url = app(CineSrcEmbed::class)->buildUrl(1396, 'tv', 1, 1, [
        'autoskip' => true,
    ]);

    expect($url)->toContain('autoskip=true');
});
