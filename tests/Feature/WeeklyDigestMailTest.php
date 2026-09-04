<?php

use App\Mail\WeeklyDigest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response([
            'results' => [
                [
                    'id' => 550,
                    'title' => 'Fight Club',
                    'vote_average' => 8.4,
                    'poster_path' => '/poster.jpg',
                ],
            ],
        ]),
    ]);
});

test('weekly digest respects free-tier send limit', function () {
    Mail::fake();

    User::factory()->count(5)->create([
        'email_verified_at' => now(),
    ]);
    User::factory()->unverified()->create();

    $this->artisan('app:send-weekly-digest', ['--limit' => 2])
        ->assertSuccessful()
        ->expectsOutputToContain('limit 2');

    Mail::assertQueued(WeeklyDigest::class, 2);
});

test('weekly digest uses configured default limit', function () {
    Mail::fake();
    config(['mail.digest_daily_limit' => 3]);

    User::factory()->count(5)->create([
        'email_verified_at' => now(),
    ]);

    $this->artisan('app:send-weekly-digest')
        ->assertSuccessful()
        ->expectsOutputToContain('limit 3');

    Mail::assertQueued(WeeklyDigest::class, 3);
});

test('weekly digest skips unverified users', function () {
    Mail::fake();

    User::factory()->unverified()->count(3)->create();

    $this->artisan('app:send-weekly-digest', ['--limit' => 10])
        ->assertSuccessful();

    Mail::assertNothingQueued();
});
