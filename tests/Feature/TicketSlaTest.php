<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketSlaAlertNotification;
use App\Services\TicketSlaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketSlaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_urgent_ticket_gets_priority_based_deadlines_and_warning_once(): void
    {
        Notification::fake();

        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $now = Carbon::parse('2026-11-22 12:00:00');

        $ticket = Ticket::query()->forceCreate([
            'subject' => 'Urgent SLA test ticket',
            'description' => 'Verify the urgent SLA warning is sent once.',
            'status' => 'open',
            'priority' => 'urgent',
            'user_id' => $customer->id,
            'assigned_to' => $agent->id,
            'created_at' => $now->copy()->subMinutes(10),
            'updated_at' => $now->copy()->subMinutes(10),
        ]);

        $service = app(TicketSlaService::class);
        $service->process((int) $ticket->getKey(), $now);
        $ticket->refresh();

        $this->assertTrue($ticket->first_response_due_at->equalTo($now->copy()->addMinutes(5)));
        $this->assertTrue($ticket->resolution_due_at->equalTo($now->copy()->addHours(3)->addMinutes(50)));
        $this->assertNotNull($ticket->first_response_warning_sent_at);
        $this->assertNull($ticket->first_response_breached_at);

        $service->process((int) $ticket->getKey(), $now->copy()->addMinute());
        $this->assertCount(1, Notification::sent($agent, TicketSlaAlertNotification::class));
    }

    public function test_overdue_first_response_is_marked_as_breached_and_alerted(): void
    {
        Notification::fake();

        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $createdAt = Carbon::parse('2026-11-22 10:00:00');

        $ticket = Ticket::query()->forceCreate([
            'subject' => 'Overdue SLA test ticket',
            'description' => 'Verify the first response breach is recorded.',
            'status' => 'open',
            'priority' => 'urgent',
            'user_id' => $customer->id,
            'assigned_to' => $agent->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        app(TicketSlaService::class)->process((int) $ticket->getKey(), $createdAt->copy()->addMinutes(20));
        $ticket->refresh();

        $this->assertNotNull($ticket->first_response_breached_at);
        Notification::assertSentTo($agent, TicketSlaAlertNotification::class, function (TicketSlaAlertNotification $notification): bool {
            return $notification->slaType === 'first_response'
                && $notification->state === 'breached';
        });
    }
}
