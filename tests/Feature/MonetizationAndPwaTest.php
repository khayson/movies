<?php

test('web app manifest exists in public assets', function () {
    expect(file_exists(public_path('manifest.webmanifest')))->toBeTrue();

    $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);

    expect($manifest)
        ->toHaveKey('display', 'standalone')
        ->toHaveKey('start_url', '/');
});

test('service worker script exists in public assets', function () {
    expect(file_exists(public_path('sw.js')))->toBeTrue();
    expect((string) file_get_contents(public_path('sw.js')))->toContain('streamvault-shell-v1');
});

test('home page includes pwa head tags', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('rel="manifest"', false)
        ->assertSee('/manifest.webmanifest', false)
        ->assertSee('apple-mobile-web-app-capable', false);
});

test('support link appears in footer when configured', function () {
    config([
        'services.support.url' => 'https://ko-fi.com/example',
        'services.support.label' => 'Buy me a coffee',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('https://ko-fi.com/example', false)
        ->assertSee('Buy me a coffee');
});

test('support link is hidden when not configured', function () {
    config([
        'services.support.url' => null,
        'services.support.label' => 'Buy me a coffee',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Buy me a coffee');
});
