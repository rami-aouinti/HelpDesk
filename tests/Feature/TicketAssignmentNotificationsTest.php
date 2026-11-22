<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use App\Services\TicketNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketAssignmentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_newly_assigned_agent_receives_a_notification_and_actor_does_not(): void
    {
        Notification::fake();

        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();

        $ticket = Ticket::query()->create([
            'subject' => 'Assignment notification regression',
            'description' => 'A newly assigned agent should be notified.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'assigned_to' => $agent->id,
        ]);

        app(TicketNotificationService::class)->ticketUpdated(
            $ticket->load(['customer', 'assignedAgent', 'team']),
            $admin,
            ['assigned_to' => ['old' => null, 'new' => $agent->id]],
        );

        Notification::assertSentTo($agent, TicketActivityNotification::class, function (TicketActivityNotification $notification) use ($ticket): bool {
            return $notification->ticket->is($ticket)
                && $notification->subject === 'Assignment updated for ticket #' . $ticket->id
                && ($notification->changes['Assigned agent'] ?? null) !== null;
        });

        Notification::assertNotSentTo($admin, TicketActivityNotification::class);
    }

    public function test_old_assignee_is_notified_when_ticket_is_reassigned(): void
    {
        Notification::fake();

        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $oldAgent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $newAgent = User::query()->where('email', 'sarah.agent@helpdesk.test')->firstOrFail();

        $ticket = Ticket::query()->create([
            'subject' => 'Reassignment notification regression',
            'description' => 'Both agents should be informed about reassignment.',
            'status' => 'in_progress',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'assigned_to' => $newAgent->id,
        ]);

        app(TicketNotificationService::class)->ticketUpdated(
            $ticket->load(['customer', 'assignedAgent', 'team']),
            $admin,
            ['assigned_to' => ['old' => $oldAgent->id, 'new' => $newAgent->id]],
        );

        Notification::assertSentTo($newAgent, TicketActivityNotification::class);
        Notification::assertSentTo($oldAgent, TicketActivityNotification::class);
        Notification::assertNotSentTo($admin, TicketActivityNotification::class);
    }
}
