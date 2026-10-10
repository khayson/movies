<?php

use App\Support\SocialMeta;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;

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
            'episode_run_time' => [45],
            'external_ids' => ['imdb_id' => 'tt0137523'],
        ]),
        '*' => Http::response([]),
    ]);
});

test('social meta prefers w1280 backdrop urls for share images', function () {
    SocialMeta::forMedia([
        'title' => 'Fight Club',
        'overview' => 'An insomniac office worker and a soap maker form an underground fight club.',
        'backdrop_path' => '/fCayJrkfRaCRCTh8GqN30f8oyQF.jpg',
        'poster_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg',
        'release_date' => '1999-10-15',
    ], 'movie', 'https://example.test/movies/550');

    expect(View::shared('ogTitle'))->toBe('Fight Club (1999)')
        ->and(View::shared('ogType'))->toBe('video.movie')
        ->and(View::shared('ogUrl'))->toBe('https://example.test/movies/550')
        ->and(View::shared('ogImage'))->toContain('/w1280/')
        ->and(View::shared('ogImage'))->toContain('/fCayJrkfRaCRCTh8GqN30f8oyQF.jpg')
        ->and(View::shared('ogDescription'))->toContain('insomniac');
});

test('social meta falls back to poster when backdrop is missing', function () {
    SocialMeta::forMedia([
        'name' => 'Breaking Bad',
        'overview' => '',
        'poster_path' => '/ggFHVNu6YYI2Bz1KyjrUIHxVVsb.jpg',
        'first_air_date' => '2008-01-20',
    ], 'tv');

    expect(View::shared('ogTitle'))->toBe('Breaking Bad (2008)')
        ->and(View::shared('ogType'))->toBe('video.tv_show')
        ->and(View::shared('ogImage'))->toContain('/w780/')
        ->and(View::shared('ogDescription'))->toContain('Breaking Bad');
});

test('movie detail page exposes rich open graph tags for crawlers', function () {
    $this->get(route('movies.detail', 550))
        ->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('Fight Club (1999)', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('/w1280/', false)
        ->assertSee('property="og:image:alt"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertSee('rel="canonical"', false);
});

test('watch page exposes open graph tags so shared player links preview correctly', function () {
    $this->get(route('watch', ['type' => 'movie', 'tmdbId' => 550]))
        ->assertOk()
        ->assertSee('property="og:title"', false)
        ->assertSee('Fight Club (1999)', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('/w1280/', false)
        ->assertSee('Share', false)
        ->assertSee('WhatsApp', false);
});

test('share text includes a modern pitch without only dumping the raw url', function () {
    $text = SocialMeta::shareText([
        'title' => 'Fight Club',
        'release_date' => '1999-10-15',
    ], 'movie');

    expect($text)->toContain('Fight Club (1999)')
        ->and($text)->toContain(config('app.name'))
        ->and($text)->toContain('Watch');
});
