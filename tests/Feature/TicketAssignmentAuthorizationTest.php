<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_agent_cannot_assign_or_reassign_a_ticket(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), [
                'team_id' => $team->id,
                'assigned_to' => $agent->id,
            ])
            ->assertForbidden();

        $this->assertNull($ticket->fresh()->team_id);
        $this->assertNull($ticket->fresh()->assigned_to);
        $this->assertDatabaseMissing('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'assigned_to',
        ]);
    }

    public function test_admin_can_reassign_a_ticket_to_another_member_of_its_team(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();
        $previousAgent = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();
        $nextAgent = User::where('email', 'daniel.agent@helpdesk.test')->firstOrFail();

        $ticket = $this->makeTicket($customer, [
            'team_id' => $team->id,
            'assigned_to' => $previousAgent->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.update', $ticket), [
                'assigned_to' => $nextAgent->id,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame($nextAgent->id, $ticket->fresh()->assigned_to);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'action' => 'assigned_to',
            'old_value' => (string) $previousAgent->id,
            'new_value' => (string) $nextAgent->id,
        ]);
    }

    public function test_agent_can_follow_an_allowed_status_transition(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), ['status' => 'in_progress'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame('in_progress', $ticket->fresh()->status);
    }

    public function test_agent_cannot_skip_the_ticket_workflow(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($agent)
            ->from(route('tickets.show', $ticket))
            ->patch(route('tickets.update', $ticket), ['status' => 'resolved'])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('status');

        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertDatabaseMissing('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'status',
        ]);
    }

    public function test_admin_can_override_the_standard_status_transition_flow(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($admin)
            ->patch(route('tickets.update', $ticket), ['status' => 'closed'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame('closed', $ticket->fresh()->status);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeTicket(User $customer, array $attributes = []): Ticket
    {
        return Ticket::create([
            'subject' => 'Ticket assignment authorization',
            'description' => 'A ticket used to verify assignment and workflow permissions.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            ...$attributes,
        ]);
    }
}
