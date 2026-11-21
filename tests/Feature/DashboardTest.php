<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_agent_dashboard_hides_tickets_from_projects_the_agent_does_not_belong_to(): void
    {
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::role('admin')->firstOrFail();
        $otherAgent = User::query()->where('email', 'amira.agent@helpdesk.test')->firstOrFail();
        $team = $agent->teams()->firstOrFail();

        $project = Project::query()->create([
            'name' => 'Dashboard private project',
            'created_by' => $admin->id,
        ]);
        $project->members()->attach($otherAgent);

        $ticket = $this->createTicket($customer, [
            'team_id' => $team->id,
            'project_id' => $project->id,
            'subject' => 'Private project ticket for dashboard',
        ]);

        $this->actingAs($agent)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($ticket->subject);
    }

    public function test_customer_dashboard_hides_own_tickets_from_projects_the_customer_does_not_belong_to(): void
    {
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $admin = User::role('admin')->firstOrFail();
        $otherCustomer = User::query()->where('email', 'sophie.customer@helpdesk.test')->firstOrFail();

        $project = Project::query()->create([
            'name' => 'Customer private dashboard project',
            'created_by' => $admin->id,
        ]);
        $project->members()->attach($otherCustomer);

        $ticket = $this->createTicket($customer, [
            'project_id' => $project->id,
            'subject' => 'Customer project membership restricted ticket',
        ]);

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($ticket->subject);
    }

    public function test_admin_dashboard_can_see_tickets_from_all_projects(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherAgent = User::query()->where('email', 'amira.agent@helpdesk.test')->firstOrFail();

        $project = Project::query()->create([
            'name' => 'Admin global dashboard project',
            'created_by' => $admin->id,
        ]);
        $project->members()->attach($otherAgent);

        $ticket = $this->createTicket($customer, [
            'project_id' => $project->id,
            'subject' => 'Admin global visibility ticket',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($ticket->subject);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTicket(User $customer, array $attributes = []): Ticket
    {
        return Ticket::query()->create([
            'subject' => 'Dashboard visibility test ticket',
            'description' => 'A ticket used to verify dashboard visibility.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            ...$attributes,
        ]);
    }
}
