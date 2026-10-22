<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/3/trending/*' => Http::response(['results' => [
            ['id' => 101, 'title' => 'Wall Movie', 'poster_path' => '/wall.jpg', 'media_type' => 'movie'],
            ['id' => 5, 'name' => 'A Person', 'profile_path' => '/p.jpg', 'media_type' => 'person'],
        ]]),
        'api.themoviedb.org/3/discover/movie*' => Http::response(['results' => [
            ['id' => 202, 'title' => 'Lagos Nights', 'poster_path' => '/lagos.jpg'],
        ]]),
        'api.themoviedb.org/3/discover/tv*' => Http::response(['results' => [
            ['id' => 303, 'name' => 'Spring Anime', 'poster_path' => '/anime.jpg'],
        ]]),
        '*' => Http::response(['results' => []]),
    ]);
});

test('guests see the welcome landing with sign-up calls to action', function () {
    $this->get(route('welcome'))
        ->assertOk()
        ->assertSee('Every new movie, series and anime.')
        ->assertSee("Get Started — it's free")
        ->assertSee(route('register'), false)
        ->assertSee('Browse without an account')
        ->assertSee('Frequently asked questions')
        ->assertSee('Does StreamVault host the videos?')
        ->assertSee('Lagos Nights')
        ->assertSee('Spring Anime')
        ->assertSee('https://image.tmdb.org/t/p/w342/wall.jpg', false);
});

test('signed-in viewers are sent straight to the home page from the welcome page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('welcome'))
        ->assertOk()
        ->assertSee('Start watching')
        ->assertDontSee('Browse without an account');
});

test('the welcome page still renders when tmdb is unavailable', function () {
    Http::fake(['*' => Http::failedConnection()]);

    $this->get(route('welcome'))->assertOk()->assertSee('Frequently asked questions');
});
