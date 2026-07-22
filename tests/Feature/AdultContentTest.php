<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [],
            'total_pages' => 1,
        ]),
    ]);
});

test('guests cannot access adult browse page', function () {
    $this->get(route('adult.browse'))->assertRedirect(route('login'));
});

test('underage users are blocked from adult browse page', function () {
    $user = User::factory()->create([
        'date_of_birth' => now()->subYears(16),
        'preferences' => ['show_adult_content' => true],
    ]);

    $this->actingAs($user)
        ->get(route('adult.browse'))
        ->assertForbidden();
});

test('adult users without preference enabled are blocked', function () {
    $user = User::factory()->create([
        'date_of_birth' => now()->subYears(25),
        'preferences' => ['show_adult_content' => false],
    ]);

    $this->actingAs($user)
        ->get(route('adult.browse'))
        ->assertForbidden();
});

test('verified adult users can access adult browse page', function () {
    $user = User::factory()->create([
        'date_of_birth' => now()->subYears(25),
        'preferences' => ['show_adult_content' => true],
    ]);

    $this->actingAs($user)
        ->get(route('adult.browse'))
        ->assertOk()
        ->assertSee('Adult Content');
});

test('user without date of birth cannot enable adult content', function () {
    $user = User::factory()->create([
        'date_of_birth' => null,
        'preferences' => null,
    ]);

    expect($user->canViewAdultContent())->toBeFalse();
    expect($user->isAdult())->toBeFalse();
});

test('adult link only visible in navbar for verified adult users', function () {
    $adultUser = User::factory()->create([
        'date_of_birth' => now()->subYears(25),
        'preferences' => ['show_adult_content' => true],
    ]);

    $this->actingAs($adultUser)
        ->get(route('home'))
        ->assertSee('18+');

    $regularUser = User::factory()->create([
        'date_of_birth' => null,
        'preferences' => null,
    ]);

    $this->actingAs($regularUser)
        ->get(route('home'))
        ->assertDontSee('18+');
});
