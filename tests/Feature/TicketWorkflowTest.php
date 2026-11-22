<?php

namespace Tests\Feature;

use App\Models\Project;
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

    public function test_agent_cannot_make_an_invalid_status_transition(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Resolved issue',
            'description' => 'This ticket is already resolved.',
            'status' => 'resolved',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->from(route('tickets.show', $ticket))
            ->patch(route('tickets.update', $ticket), ['status' => 'in_progress'])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('status');

        $this->assertSame('resolved', $ticket->fresh()->status);
        $this->assertDatabaseMissing('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'status',
            'new_value' => 'in_progress',
        ]);
    }

    public function test_admin_can_override_the_status_workflow(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Closed issue',
            'description' => 'An administrator needs to reopen it.',
            'status' => 'closed',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.update', $ticket), ['status' => 'open'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'action' => 'status',
            'old_value' => 'closed',
            'new_value' => 'open',
        ]);
    }

    public function test_admin_cannot_assign_a_ticket_to_an_inactive_agent(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $agent->update(['is_active' => false]);

        $ticket = Ticket::create([
            'subject' => 'Inactive agent assignment',
            'description' => 'An inactive agent must not receive new work.',
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

        $this->assertNull($ticket->fresh()->assigned_to);
        $this->assertNull($ticket->fresh()->team_id);
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

    public function test_agent_can_change_ticket_priority_and_the_change_is_recorded(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Slow application',
            'description' => 'The application responds slowly.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), ['priority' => 'urgent'])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 'urgent',
        ]);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'priority',
            'old_value' => 'normal',
            'new_value' => 'urgent',
        ]);
    }

    public function test_admin_can_assign_a_ticket_to_a_team_without_an_agent(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Unassigned team request',
            'description' => 'This ticket should belong to a team.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.update', $ticket), ['team_id' => $team->id])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'team_id' => $team->id,
            'assigned_to' => null,
        ]);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'action' => 'team_id',
            'old_value' => null,
            'new_value' => (string) $team->id,
        ]);
    }

    public function test_admin_cannot_move_a_ticket_away_from_its_assigned_agent_team(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $currentTeam = Team::where('name', 'General Support')->firstOrFail();
        $otherTeam = Team::where('name', 'Technical Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Assigned team request',
            'description' => 'The assigned agent belongs to the current team.',
            'status' => 'in_progress',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'team_id' => $currentTeam->id,
            'assigned_to' => $agent->id,
        ]);

        $this->actingAs($admin)
            ->from(route('tickets.show', $ticket))
            ->patch(route('tickets.update', $ticket), ['team_id' => $otherTeam->id])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('team_id');

        $this->assertSame($currentTeam->id, $ticket->fresh()->team_id);
        $this->assertSame($agent->id, $ticket->fresh()->assigned_to);
    }

    public function test_invalid_ticket_priority_is_rejected_by_request_validation(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Slow application',
            'description' => 'The application responds slowly.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->from(route('tickets.show', $ticket))
            ->patch(route('tickets.update', $ticket), ['priority' => 'critical'])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('priority');

        $this->assertSame('normal', $ticket->fresh()->priority);
    }

    public function test_agent_can_link_a_ticket_to_a_project_they_belong_to(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $project = Project::create([
            'name' => 'Agent project access',
            'description' => 'A project assigned to this agent.',
            'status' => 'active',
            'created_by' => User::where('email', 'admin@helpdesk.test')->value('id'),
        ]);
        $project->members()->attach($agent->id);

        $ticket = Ticket::create([
            'subject' => 'Ticket to link',
            'description' => 'Link this ticket to the agent project.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), ['project_id' => $project->id])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasNoErrors();

        $this->assertSame($project->id, $ticket->fresh()->project_id);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'project_id',
            'old_value' => null,
            'new_value' => (string) $project->id,
        ]);
    }

    public function test_agent_cannot_link_a_ticket_to_a_project_they_do_not_belong_to(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $project = Project::create([
            'name' => 'Restricted project',
            'description' => 'No membership for the current agent.',
            'status' => 'active',
            'created_by' => User::where('email', 'admin@helpdesk.test')->value('id'),
        ]);

        $ticket = Ticket::create([
            'subject' => 'Ticket stays unlinked',
            'description' => 'The agent must not link this ticket.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $this->actingAs($agent)
            ->from(route('tickets.show', $ticket))
            ->patch(route('tickets.update', $ticket), ['project_id' => $project->id])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('project_id');

        $this->assertNull($ticket->fresh()->project_id);
        $this->assertDatabaseMissing('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'project_id',
            'new_value' => (string) $project->id,
        ]);
    }

    public function test_agent_can_unlink_a_ticket_from_its_project(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $project = Project::create([
            'name' => 'Unlinkable project',
            'description' => 'The agent belongs to this project.',
            'status' => 'active',
            'created_by' => User::where('email', 'admin@helpdesk.test')->value('id'),
        ]);
        $project->members()->attach($agent->id);

        $ticket = Ticket::create([
            'subject' => 'Ticket to unlink',
            'description' => 'Remove the project association.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'project_id' => $project->id,
        ]);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), ['project_id' => null])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasNoErrors();

        $this->assertNull($ticket->fresh()->project_id);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'action' => 'project_id',
            'old_value' => (string) $project->id,
            'new_value' => null,
        ]);
    }


    public function test_admin_can_unassign_an_agent_without_removing_the_team(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Ticket to unassign',
            'description' => 'Keep the team but remove the agent.',
            'status' => 'in_progress',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'team_id' => $team->id,
            'assigned_to' => $agent->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('tickets.update', $ticket), ['assigned_to' => null])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasNoErrors();

        $this->assertNull($ticket->fresh()->assigned_to);
        $this->assertSame($team->id, $ticket->fresh()->team_id);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'action' => 'assigned_to',
            'old_value' => (string) $agent->id,
            'new_value' => null,
        ]);
    }

    public function test_agent_cannot_unassign_a_ticket_through_the_web_workflow(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Protected assignment',
            'description' => 'Only an administrator may unassign this ticket.',
            'status' => 'in_progress',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'team_id' => $team->id,
            'assigned_to' => $agent->id,
        ]);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), ['assigned_to' => null])
            ->assertForbidden();

        $this->assertSame($agent->id, $ticket->fresh()->assigned_to);
    }


    public function test_agent_can_update_ticket_effort_and_history_is_recorded(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Effort tracking ticket',
            'description' => 'Estimate and track work.',
            'status' => 'in_progress',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'estimated_minutes' => 90,
            'elapsed_minutes' => 15,
        ]);

        $this->actingAs($agent)
            ->patch(route('tickets.update', $ticket), ['elapsed_minutes' => 35])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasNoErrors();

        $this->assertSame(90, $ticket->fresh()->estimated_minutes);
        $this->assertSame(35, $ticket->fresh()->elapsed_minutes);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'elapsed_minutes',
            'old_value' => '15',
            'new_value' => '35',
        ]);
    }

    public function test_effort_values_above_the_limit_are_rejected(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $ticket = Ticket::create([
            'subject' => 'Effort limit ticket',
            'description' => 'Reject invalid effort.',
            'status' => 'in_progress',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'estimated_minutes' => 90,
            'elapsed_minutes' => 15,
        ]);

        $this->actingAs($agent)
            ->from(route('tickets.show', $ticket))
            ->patch(route('tickets.update', $ticket), ['estimated_minutes' => 525601])
            ->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHasErrors('estimated_minutes');

        $this->assertSame(90, $ticket->fresh()->estimated_minutes);
    }

}
