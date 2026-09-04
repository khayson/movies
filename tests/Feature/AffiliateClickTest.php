<?php

use App\Models\AffiliateClick;
use App\Models\User;

test('affiliate click is recorded with valid payload', function () {
    $response = $this->postJson(route('affiliate.click'), [
        'service_name' => 'Netflix',
        'service_id' => 'netflix',
        'tmdb_id' => 550,
        'media_type' => 'movie',
        'link' => 'https://www.netflix.com/title/123',
    ]);

    $response->assertOk()->assertJson(['ok' => true]);

    $click = AffiliateClick::query()->first();

    expect($click)->not->toBeNull()
        ->and($click->service_name)->toBe('Netflix')
        ->and($click->tmdb_id)->toBe(550)
        ->and($click->media_type)->toBe('movie')
        ->and($click->user_id)->toBeNull();
});

test('affiliate click attaches authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('affiliate.click'), [
            'service_name' => 'Disney+',
            'service_id' => 'disney',
            'tmdb_id' => 603,
            'media_type' => 'movie',
            'link' => 'https://www.disneyplus.com/movies/foo',
        ])
        ->assertOk();

    expect(AffiliateClick::query()->first()?->user_id)->toBe($user->id);
});

test('affiliate click rejects invalid payload', function () {
    $this->postJson(route('affiliate.click'), [
        'service_name' => '',
        'tmdb_id' => 0,
        'media_type' => 'anime',
        'link' => 'not-a-url',
    ])->assertUnprocessable();

    expect(AffiliateClick::count())->toBe(0);
});
