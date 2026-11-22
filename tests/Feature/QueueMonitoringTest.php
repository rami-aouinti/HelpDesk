<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\IndexUserInElasticsearch;
use App\Listeners\IndexTicketInElasticsearch;
use App\Notifications\TicketActivityNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\TagRepository;
use Laravel\Horizon\Tags;
use Mockery;
use Tests\TestCase;

class QueueMonitoringTest extends TestCase
{
    public function test_queue_busy_event_logs_the_connection_name_and_backlog_size(): void
    {
        Log::spy();

        Event::dispatch(new QueueBusy('redis', 'default', 101));

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('queue.backlog.threshold_exceeded', [
                'event' => 'queue.backlog.threshold_exceeded',
                'connection' => 'redis',
                'queue' => 'default',
                'size' => 101,
            ]);
    }

    public function test_horizon_monitor_tags_command_registers_helpdesk_search_tags(): void
    {
        $tags = Mockery::mock(TagRepository::class);
        $tags->shouldReceive('monitor')->once()->with('ticket-notifications');
        $tags->shouldReceive('monitor')->once()->with('ticket-search-indexing');
        $tags->shouldReceive('monitor')->once()->with('user-search-indexing');

        $this->app->instance(TagRepository::class, $tags);

        $this->artisan('horizon:monitor-tags')
            ->expectsOutput('Horizon monitoring tags registered: ticket-notifications, ticket-search-indexing, user-search-indexing.')
            ->assertExitCode(0);
    }

    public function test_scheduler_checks_both_redis_queues_and_registers_horizon_tags(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->pluck('command')
            ->filter()
            ->values();

        $this->assertTrue($commands->contains(
            fn (string $command): bool => str_contains($command, 'queue:monitor redis:default,redis:notifications --max=100'),
        ));
        $this->assertTrue($commands->contains(
            fn (string $command): bool => str_contains($command, 'horizon:monitor-tags'),
        ));
        $this->assertTrue($commands->contains(
            fn (string $command): bool => str_contains($command, 'horizon:snapshot'),
        ));
    }

    public function test_search_indexing_jobs_expose_stable_horizon_monitoring_tags(): void
    {
        $this->assertSame(
            ['ticket-search-indexing'],
            (new IndexTicketInElasticsearch())->tags(),
        );

        $this->assertSame(['ticket-notifications'], (new TicketActivityNotification(new \App\Models\Ticket(), 'Subject', 'Message'))->tags());

        $this->assertSame(
            ['user-search-indexing'],
            Tags::for(new IndexUserInElasticsearch(42)),
        );
    }
}
