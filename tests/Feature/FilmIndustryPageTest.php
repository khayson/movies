<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [
                [
                    'id' => 1001,
                    'title' => 'Lagos Story',
                    'name' => 'Lagos Story',
                    'overview' => 'A test title',
                    'poster_path' => '/poster.jpg',
                    'vote_average' => 7.1,
                    'release_date' => '2024-05-01',
                ],
            ],
            'total_pages' => 3,
        ]),
    ]);
});

test('film industry pages list titles from their origin country', function (string $route, string $name, string $country) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee($name)
        ->assertSee('Lagos Story');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/discover/movie')
        && $request['with_origin_country'] === $country);
})->with([
    'nollywood' => ['nollywood', 'Nollywood', 'NG'],
    'ghallywood' => ['ghallywood', 'Ghallywood', 'GH'],
]);

test('film industry page switches to tv shows', function () {
    Livewire::test('pages::film-industry', ['industry' => 'nollywood'])
        ->call('setTab', 'tv')
        ->assertSet('tab', 'tv')
        ->assertSet('page', 1);

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/discover/tv')
        && $request['with_origin_country'] === 'NG');
});

test('film industry page rejects unknown industries', function () {
    Livewire::test('pages::film-industry', ['industry' => 'hollywood'])->assertNotFound();
});
