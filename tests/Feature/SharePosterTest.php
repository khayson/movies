<?php

use Illuminate\Support\Facades\Http;

test('share poster proxies allowed tmdb images', function () {
    Http::fake([
        'image.tmdb.org/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    $src = 'https://image.tmdb.org/t/p/w780/poster.jpg';

    $this->get(route('share.poster', ['src' => $src]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertSee('fake-image-bytes', false);
});

test('share poster rejects non-tmdb urls', function () {
    $this->get(route('share.poster', ['src' => 'https://evil.example/x.jpg']))
        ->assertNotFound();
});

test('share menu offers poster sharing for status when an image is present', function () {
    $html = view('partials.share-buttons', [
        'shareTitle' => 'Fight Club',
        'shareText' => 'Watch Fight Club',
        'shareUrl' => 'https://example.test/movies/550',
        'shareImage' => 'https://image.tmdb.org/t/p/w780/poster.jpg',
    ])->render();

    expect($html)
        ->toContain('Share poster (Status)')
        ->toContain('sharePoster()')
        ->toContain('/share/poster?src=')
        ->toContain('WhatsApp chat');
});
