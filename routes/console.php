<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Horizon\Contracts\TagRepository;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Horizon monitored tags
|--------------------------------------------------------------------------
|
| Horizon's Monitoring screen tracks job tags, not worker supervisors.
| Keep the search-indexing tags registered in Horizon's Redis repository.
| Redis SADD is idempotent, so this command is safe to run repeatedly and
| restores the monitored tags after the Horizon Redis data is recreated.
|
*/
Artisan::command('horizon:monitor-tags', function (TagRepository $tags): void {
    foreach (['ticket-notifications', 'ticket-search-indexing', 'user-search-indexing'] as $tag) {
        $tags->monitor($tag);
    }

    $this->info('Horizon monitoring tags registered: ticket-notifications, ticket-search-indexing, user-search-indexing.');
})->purpose('Register HelpDesk job tags in Horizon Monitoring');

Schedule::command('queue:monitor redis:default,redis:notifications --max=100')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('horizon:monitor-tags')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('horizon:snapshot')->everyFiveMinutes();

Schedule::command('helpdesk:process-slas')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('helpdesk:dispatch-sla-alerts')
    ->everyMinute()
    ->withoutOverlapping();
