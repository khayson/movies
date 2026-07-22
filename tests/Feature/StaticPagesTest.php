<?php

test('terms page returns a successful response', function () {
    $response = $this->get(route('terms'));

    $response->assertOk();
    $response->assertSee('Terms');
    $response->assertSee('External Content Disclaimer');
});

test('privacy page returns a successful response', function () {
    $response = $this->get(route('privacy'));

    $response->assertOk();
    $response->assertSee('Privacy Policy');
    $response->assertSee('Privacy-First Approach');
});

test('architecture page returns a successful response', function () {
    $response = $this->get(route('architecture'));

    $response->assertOk();
    $response->assertSee('Architecture');
    $response->assertSee('TMDB');
});
