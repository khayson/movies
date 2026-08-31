<?php

use Illuminate\Support\Facades\Artisan;

test('cron endpoint returns 404 without a matching secret', function () {
    config(['services.cron.secret' => 'correct-secret']);

    $this->get('/cron/wrong-secret')->assertNotFound();
});

test('cron endpoint returns 404 when secret is not configured', function () {
    config(['services.cron.secret' => null]);

    $this->get('/cron/any-token')->assertNotFound();
});

test('cron endpoint runs the scheduler with a valid secret', function () {
    config(['services.cron.secret' => 'correct-secret']);

    Artisan::shouldReceive('call')->once()->with('schedule:run')->andReturn(0);
    Artisan::shouldReceive('output')->once()->andReturn("No scheduled commands are ready to run.\n");

    $this->get('/cron/correct-secret')
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'output' => "No scheduled commands are ready to run.\n",
        ]);
});
