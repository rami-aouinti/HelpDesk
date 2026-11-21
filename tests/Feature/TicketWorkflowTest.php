<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketHistory;
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

    public function test_customer_can_create_a_ticket_and_only_sees_their_own_tickets(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'subject' => 'Cannot access my account',
                'description' => 'The password reset page does not work for me.',
                'priority' => 'high',
            ])
            ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::where('subject', 'Cannot access my account')
            ->where('user_id', $customer->id)
            ->firstOrFail();
        $this->assertSame($customer->id, $ticket->user_id);

        $this->actingAs($customer)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Cannot access my account');
    }

    public function test_agent_can_update_a_ticket_and_the_change_is_recorded(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Printer is offline',
            'description' => 'The office printer cannot be reached.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), ['status' => 'in_progress'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'status',
            'old_value' => 'open',
            'new_value' => 'in_progress',
        ]);
    }

    public function test_admin_can_assign_ticket_to_a_team_and_its_member(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'VPN connection issue',
            'description' => 'The VPN disconnects frequently.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($admin)
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

    public function test_agent_can_claim_an_unassigned_ticket_for_one_of_their_teams(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'New unassigned request',
            'description' => 'Waiting for a support agent.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->post(route('tickets.claim', $ticket), ['team_id' => $team->id])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'team_id' => $team->id,
            'assigned_to' => $agent->id,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'assigned_to',
            'old_value' => null,
            'new_value' => (string) $agent->id,
        ]);
    }

    public function test_agent_cannot_claim_ticket_for_a_team_they_do_not_belong_to(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $otherTeam = Team::where('name', 'Technical Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Unassigned technical request',
            'description' => 'This ticket must not be claimed by another team.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.claim', $ticket), ['team_id' => $otherTeam->id])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('team_id');

        $this->assertNull($ticket->fresh()->team_id);
        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_agent_cannot_claim_a_ticket_that_is_already_assigned(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();
        $alreadyAssignedAgent = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Already assigned request',
            'description' => 'Another agent is already working on it.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'team_id' => $team->id,
            'assigned_to' => $alreadyAssignedAgent->id,
        ]);

        $this->actingAs($agent)
            ->from(route('tickets.show', $ticket))
            ->post(route('tickets.claim', $ticket))
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('ticket');

        $this->assertSame($alreadyAssignedAgent->id, $ticket->fresh()->assigned_to);
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
