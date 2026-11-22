<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('queue:monitor redis:default,notifications --max=100')
    ->everyMinute()
    ->withoutOverlapping();
\nSchedule::command('horizon:snapshot')->everyFiveMinutes();\n