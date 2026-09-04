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
    config([
        'services.cron.secret' => 'correct-secret',
        'queue.default' => 'sync',
    ]);

    Artisan::shouldReceive('call')->once()->with('schedule:run')->andReturn(0);
    Artisan::shouldReceive('output')->once()->andReturn("No scheduled commands are ready to run.\n");

    $this->get('/cron/correct-secret')
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'queue_drained' => false,
            'output' => "No scheduled commands are ready to run.\n",
        ]);
});

test('cron endpoint drains the queue when not using sync', function () {
    config([
        'services.cron.secret' => 'correct-secret',
        'queue.default' => 'database',
    ]);

    Artisan::shouldReceive('call')->once()->with('schedule:run')->andReturn(0);
    Artisan::shouldReceive('call')->once()->with('queue:work', [
        '--stop-when-empty' => true,
        '--max-time' => 45,
        '--tries' => 3,
        '--sleep' => 0,
    ])->andReturn(0);
    Artisan::shouldReceive('output')->twice()->andReturn("schedule\n", "queue\n");

    $this->get('/cron/correct-secret')
        ->assertOk()
        ->assertJson([
            'ok' => true,
            'queue_drained' => true,
            'output' => "schedule\nqueue\n",
        ]);
});
