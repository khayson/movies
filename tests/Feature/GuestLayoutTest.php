<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response(['results' => [], 'genres' => []]),
        '*' => Http::response(['data' => [], 'shows' => [], 'movies' => []]),
    ]);
});

test('guest nav only turns into the frosted bar once the page is scrolled', function () {
    $content = $this->get(route('home'))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('class="site-nav sticky top-0 z-50"')
        ->and($content)->toContain('scrolled = window.scrollY > 8')
        ->and($content)->toContain('.site-nav.is-scrolled::before');
});

test('cinematic hero pages bleed underneath the overlay nav', function () {
    $content = $this->get(route('home'))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('hero-bleed')
        ->and($content)->toContain('@media (min-width: 1024px) { .hero-bleed { margin-top: -4rem; } }');
});

test('guest layout keeps blur off the nav box so fixed dropdowns escape it', function () {
    $content = $this->actingAs(User::factory()->create())
        ->get(route('activity.feed'))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('.site-nav.is-scrolled::before')
        ->and($content)->not->toContain('.site-nav { background: rgba');
});

test('notification dropdown panel is anchored to its trigger instead of a fixed nav offset', function () {
    $content = $this->actingAs(User::factory()->create())
        ->get(route('activity.feed'))
        ->assertOk()
        ->getContent();

    expect($content)->not->toContain('top-[4.5rem]')
        ->and($content)->toContain('absolute right-0 top-full z-50 mt-2');
});
