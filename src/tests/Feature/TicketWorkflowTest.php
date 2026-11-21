<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }


    public function test_agent_can_assign_ticket_to_a_team_and_its_member(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'VPN connection issue',
            'description' => 'The VPN disconnects frequently.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), [
                'team_id' => $team->id,
                'assigned_to' => $agent->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'team_id' => $team->id,
            'assigned_to' => $agent->id,
        ]);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'team_id',
            'new_value' => (string) $team->id,
        ]);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'assigned_to',
            'new_value' => (string) $agent->id,
        ]);
    }

    public function test_ticket_cannot_be_assigned_to_an_agent_outside_the_selected_team(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'amira.agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Account access issue',
            'description' => 'Cannot sign in.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($admin)
            ->from(route('tickets.show', $ticket))
            ->patch(route('tickets.update', $ticket), [
                'team_id' => $team->id,
                'assigned_to' => $agent->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('assigned_to');

        $this->assertNull($ticket->fresh()->team_id);
        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherCustomer = User::factory()->create();

        $ticket = Ticket::create([
            'subject' => 'Private customer request',
            'description' => 'This request belongs to another customer account.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $otherCustomer->id,
        ]);

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_customer_cannot_update_a_ticket(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = Ticket::create([
            'subject' => 'Need help with billing',
            'description' => 'I have a question about my latest invoice.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($customer)
            ->patch(route('tickets.update', $ticket), ['status' => 'closed'])
            ->assertForbidden();

        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertSame(0, TicketHistory::where('ticket_id', $ticket->id)->count());
    }
}
