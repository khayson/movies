<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [],
            'total_pages' => 1,
        ]),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
