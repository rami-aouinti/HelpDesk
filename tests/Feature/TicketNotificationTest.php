<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Notification::fake();
    }

    public function test_new_ticket_notifies_active_support_users(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        $this->actingAs($customer)->post(route('tickets.store'), [
            'subject' => 'Email notifications are missing',
            'description' => 'Please enable email notifications for new support tickets.',
            'priority' => 'normal',
        ])->assertRedirect(route('portal.home'));

        Notification::assertSentTo($agent, TicketActivityNotification::class);
    }

    public function test_public_customer_reply_notifies_assigned_agent(): void
    {
        [$customer, $agent, $ticket] = $this->ticketWithAssignedAgent();

        $this->actingAs($customer)->post(route('tickets.messages.store', $ticket), [
            'body' => 'I have some additional information.',
        ])->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo($agent, TicketActivityNotification::class);
        Notification::assertNotSentTo($customer, TicketActivityNotification::class);
    }

    public function test_internal_note_never_notifies_customer(): void
    {
        [$customer, $agent, $ticket] = $this->ticketWithAssignedAgent();

        $noteAuthor = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();

        $this->actingAs($noteAuthor)->post(route('tickets.messages.store', $ticket), [
            'body' => 'Internal troubleshooting details.',
            'is_internal' => true,
        ])->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo($agent, TicketActivityNotification::class);
        Notification::assertNotSentTo($customer, TicketActivityNotification::class);
    }

    public function test_status_and_priority_changes_notify_customer(): void
    {
        [$customer, $agent, $ticket] = $this->ticketWithAssignedAgent();

        $this->actingAs($agent)->patch(route('tickets.update', $ticket), [
            'status' => 'in_progress',
            'priority' => 'high',
        ])->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo($customer, TicketActivityNotification::class);
    }

    public function test_new_assignment_notifies_the_assigned_agent(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Please assign this ticket',
            'description' => 'This ticket needs an assigned support agent.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        $this->actingAs($admin)->patch(route('tickets.update', $ticket), [
            'team_id' => $team->id,
            'assigned_to' => $agent->id,
        ])->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo($agent, TicketActivityNotification::class);
    }

    /**
     * @return array{User, User, Ticket}
     */
    private function ticketWithAssignedAgent(): array
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Existing support request',
            'description' => 'An existing ticket used for notification tests.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'team_id' => $team->id,
            'assigned_to' => $agent->id,
        ]);

        return [$customer, $agent, $ticket];
    }
}
