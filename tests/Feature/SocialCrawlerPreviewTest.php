<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'id' => 550,
            'title' => 'Fight Club',
            'name' => 'Fight Club',
            'overview' => 'An insomniac office worker and a soap maker form an underground fight club.',
            'poster_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg',
            'backdrop_path' => '/fCayJrkfRaCRCTh8GqN30f8oyQF.jpg',
            'release_date' => '1999-10-15',
            'first_air_date' => '1999-10-15',
            'vote_average' => 8.4,
            'runtime' => 139,
            'status' => 'Released',
            'genres' => [['id' => 18, 'name' => 'Drama']],
            'credits' => ['cast' => []],
            'videos' => ['results' => []],
            'similar' => ['results' => []],
            'recommendations' => ['results' => []],
            'seasons' => [],
            'number_of_seasons' => 1,
        ]),
        '*' => Http::response([]),
    ]);
});

test('whatsapp crawler receives a lightweight open graph document for movie links', function () {
    $response = $this->withHeaders(['User-Agent' => 'WhatsApp/2.22.20.72 A'])
        ->get(route('movies.detail', 550));

    $response->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('Fight Club (1999)', false)
        ->assertSee('/w1280/', false)
        ->assertDontSee('livewire', false);

    expect($response->headers->get('Cache-Control'))->toContain('max-age=3600');
});

test('normal browsers still get the full movie detail page', function () {
    $this->get(route('movies.detail', 550))
        ->assertOk()
        ->assertSee('Watch Now', false)
        ->assertSee('Fight Club', false);
});

test('whatsapp crawler receives open graph tags for watch links', function () {
    $this->withHeaders(['User-Agent' => 'WhatsApp/2.22.20.72 A'])
        ->get(route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertOk()
        ->assertSee('property="og:image"', false)
        ->assertSee('Fight Club (1999)', false)
        ->assertDontSee('bindPlayerMessages', false);
});
