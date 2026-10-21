<?php

use Illuminate\Support\Facades\DB;

test('health route answers keep-alive pings without a session or database query', function () {
    DB::enableQueryLog();

    $this->get('/up')
        ->assertOk()
        ->assertCookieMissing(config('session.cookie'));

    expect(DB::getQueryLog())->toBeEmpty();
});
