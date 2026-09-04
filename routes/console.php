<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-weekly-digest')->weeklyOn(1, '9:00');
Schedule::command('app:pre-warm-sources')->everyFifteenMinutes();
Schedule::command('app:probe-provider-health')->everyFifteenMinutes();
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3 --sleep=0')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn (): bool => config('queue.default') !== 'sync');
