<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register without email verification', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    $this->get(route('dashboard'))->assertOk();

    expect(auth()->user()->email_verified_at)->not->toBeNull();
});

test('new users can register via json request and receive toast payload', function () {
    $response = $this->postJson(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertStatus(201)
        ->assertJsonStructure(['redirect'])
        ->assertSessionHas('toast');

    $toast = session('toast');
    expect($toast['dataset']['variant'])->toBe('success');

    $this->assertAuthenticated();
});

test('registration validation errors returned via json request', function () {
    $response = $this->postJson(route('register.store'), [
        'name' => '',
        'email' => 'invalid-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);

    $this->assertGuest();
});
