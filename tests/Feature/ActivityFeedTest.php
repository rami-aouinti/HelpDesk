<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_admin_can_filter_workspace_activity_by_action_and_actor(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $ticket = $this->createTicket($customer, ['subject' => 'Activity feed visible ticket']);

        TicketHistory::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'status',
            'old_value' => 'open',
            'new_value' => 'in_progress',
        ]);
        TicketHistory::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'action' => 'priority',
            'old_value' => 'normal',
            'new_value' => 'urgent',
        ]);

        $this->actingAs($admin)
            ->get(route('activity.index', ['action' => 'status', 'user' => $agent->id]))
            ->assertOk()
            ->assertSee('Activity feed visible ticket')
            ->assertSee('in progress')
            ->assertDontSee('urgent');
    }

    public function test_agent_activity_feed_hides_histories_for_projects_the_agent_cannot_access(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::role('admin')->firstOrFail();
        $otherAgent = User::where('email', 'amira.agent@helpdesk.test')->firstOrFail();
        $team = $agent->teams()->firstOrFail();

        $privateProject = Project::query()->create([
            'name' => 'Private activity project',
            'created_by' => $admin->id,
        ]);
        $privateProject->members()->attach($otherAgent);

        $ticket = $this->createTicket($customer, [
            'team_id' => $team->id,
            'project_id' => $privateProject->id,
            'subject' => 'Secret activity ticket',
        ]);

        TicketHistory::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $otherAgent->id,
            'action' => 'status',
            'old_value' => 'open',
            'new_value' => 'resolved',
        ]);

        $this->actingAs($agent)
            ->get(route('activity.index'))
            ->assertOk()
            ->assertDontSee('Secret activity ticket');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTicket(User $customer, array $attributes = []): Ticket
    {
        return Ticket::query()->create([
            'subject' => 'Activity feed test ticket',
            'description' => 'A ticket used to test activity history.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            ...$attributes,
        ]);
    }
}
