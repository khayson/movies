<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $release = now()->addMonths(2)->toDateString();

    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [
                [
                    'id' => 9001,
                    'title' => 'Future Blockbuster',
                    'name' => 'Future Series',
                    'overview' => 'A highly anticipated premiere.',
                    'backdrop_path' => '/future-bg.jpg',
                    'poster_path' => '/future.jpg',
                    'vote_average' => 0,
                    'release_date' => $release,
                    'first_air_date' => $release,
                    'popularity' => 100,
                ],
            ],
            'total_pages' => 1,
            'page' => 1,
        ]),
        '*' => Http::response([]),
    ]);
});

test('coming soon page is public and shows upcoming titles', function () {
    $this->get(route('coming-soon'))
        ->assertOk()
        ->assertSee('Coming Soon', false)
        ->assertSee('Future Blockbuster', false)
        ->assertSee('Premieres', false)
        ->assertSee('All coming soon', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('Coming Soon —', false);
});

test('coming soon tv tab loads discover results', function () {
    $this->get(route('coming-soon', ['tab' => 'tv']))
        ->assertOk()
        ->assertSee('Future Series', false)
        ->assertSee('TV Shows', false)
        ->assertSee('wire:click="setTab(\'tv\')"', false);
});

test('legacy upcoming path redirects to coming soon', function () {
    $this->get('/upcoming')
        ->assertRedirect('/coming-soon');
});

test('guest navigation includes coming soon', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('coming-soon'), false)
        ->assertSee('Coming Soon', false);
});
