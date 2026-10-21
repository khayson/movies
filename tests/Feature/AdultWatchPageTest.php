<?php

use App\Models\User;
use App\Services\AdultContentProvider;
use Illuminate\Support\Facades\Http;

function verifiedAdultViewer(): User
{
    return User::factory()->create([
        'date_of_birth' => now()->subYears(25),
        'preferences' => ['show_adult_content' => true, 'adult_lock_session' => false],
    ]);
}

test('guests cannot open the adult watch page', function () {
    $this->get(route('adult.watch', ['provider' => 'redtube', 'key' => AdultContentProvider::encodeWatchKey('42')]))
        ->assertRedirect(route('login'));
});

test('adult watch page shows the player, sources and other providers', function () {
    Http::fake([
        'api.redtube.com/*' => Http::response([
            'video' => [
                'video_id' => '42',
                'title' => 'Late Night Pick',
                'duration' => '10:00',
                'views' => '1,500',
                'rating' => '4.2',
                'default_thumb' => 'https://cdn.example.com/r.jpg',
            ],
        ]),
    ]);

    $this->actingAs(verifiedAdultViewer())
        ->get(route('adult.watch', ['provider' => 'redtube', 'key' => AdultContentProvider::encodeWatchKey('42')]))
        ->assertOk()
        ->assertSee('Late Night Pick')
        ->assertSee('https://embed.redtube.com/?id=42', false)
        ->assertSee('RedTube player')
        ->assertSee('Find it on other sources')
        ->assertSee(route('adult.browse', ['provider' => 'eporner', 'q' => 'Late Night Pick']));
});

test('adult watch page rejects videos from foreign sites', function () {
    $this->actingAs(verifiedAdultViewer())
        ->get(route('adult.watch', ['provider' => 'xnxx', 'key' => AdultContentProvider::encodeWatchKey('https://evil.example.com/video-1/x')]))
        ->assertNotFound();
});

test('adult browse links tube videos to the watch page', function () {
    config(['sources.rapidapi_key' => null]);

    Http::fake([
        'www.eporner.com/api/v2/video/search/*' => Http::response([
            'total_count' => 1,
            'videos' => [[
                'id' => 'AbC123',
                'title' => 'Weekly Pick',
                'length_min' => '12:00',
                'views' => 10,
                'rate' => '4.5',
                'default_thumb' => ['src' => 'https://cdn.example.com/e.jpg'],
            ]],
        ]),
    ]);

    $this->actingAs(verifiedAdultViewer())
        ->get(route('adult.browse', ['provider' => 'eporner']))
        ->assertOk()
        ->assertSee(route('adult.watch', ['provider' => 'eporner', 'key' => AdultContentProvider::encodeWatchKey('AbC123')]), false);
});
