<?php

use App\Models\QuizScore;
use App\Models\User;

test('trending page loads', function () {
    $this->get(route('trending'))
        ->assertOk();
});

test('trending page accepts tab parameter', function () {
    $this->get(route('trending', ['tab' => 'popular']))
        ->assertOk();
});

test('quiz page loads', function () {
    $this->get(route('quiz'))
        ->assertOk();
});

test('quiz score can be saved', function () {
    $user = User::factory()->create();

    $score = QuizScore::factory()->create([
        'user_id' => $user->id,
        'score' => 8,
        'total' => 10,
    ]);

    expect($score->user_id)->toBe($user->id);
    expect($score->score)->toBe(8);
    expect($score->total)->toBe(10);
    expect($user->quizScores()->count())->toBe(1);
});

test('weekly digest command exists', function () {
    $this->artisan('app:send-weekly-digest')
        ->assertSuccessful();
});

test('home page has og meta tags', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('og:title', escape: false)
        ->assertSee('og:description', escape: false);
});
