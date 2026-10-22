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
        ->and($content)->toContain('.hero-bleed { margin-top: -4rem; }');
});

test('guest layout keeps blur off the nav box so fixed dropdowns escape it', function () {
    $content = $this->actingAs(User::factory()->create())
        ->get(route('activity.feed'))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('.site-nav.is-scrolled::before')
        ->and($content)->not->toContain('.site-nav { background: rgba');
});

test('the header groups secondary sections into menus and offers a mobile drawer', function () {
    $content = $this->get(route('home'))->assertOk()->getContent();

    expect($content)->toContain('African Cinema')
        ->and($content)->toContain(route('nollywood'))
        ->and($content)->toContain('id="site-menu"')
        ->and($content)->toContain('aria-label="Open menu"');
});

test('signed-out visitors get a sign-up band in the footer that links to the welcome page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee("Make it yours. It's free.", false)
        ->assertSee(route('welcome'), false);

    $this->actingAs(User::factory()->create())
        ->get(route('activity.feed'))
        ->assertOk()
        ->assertDontSee("Make it yours. It's free.", false);
});

test('notification dropdown panel is anchored to its trigger instead of a fixed nav offset', function () {
    $content = $this->actingAs(User::factory()->create())
        ->get(route('activity.feed'))
        ->assertOk()
        ->getContent();

    expect($content)->not->toContain('top-[4.5rem]')
        ->and($content)->toContain('absolute right-0 top-full z-50 mt-2');
});
