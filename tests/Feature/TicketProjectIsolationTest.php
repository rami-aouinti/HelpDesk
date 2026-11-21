<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketProjectIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_agent_cannot_list_view_or_reply_to_a_ticket_in_a_project_they_do_not_belong_to(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();
        $project = $this->makeProject($admin);
        $project->members()->attach($admin->id);
        $ticket = $this->makeTicket($customer, $team, $project);

        Sanctum::actingAs($agent);

        $response = $this->getJson('/api/v1/tickets')->assertOk();
        $visibleIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertNotContains($ticket->id, $visibleIds);

        $this->getJson("/api/v1/tickets/{$ticket->id}")->assertForbidden();

        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", [
            'body' => 'This project reply must be rejected.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'This project reply must be rejected.',
        ]);
    }

    public function test_agent_can_access_a_project_ticket_after_becoming_a_project_member(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();
        $project = $this->makeProject($admin);
        $project->members()->attach([$admin->id, $agent->id]);
        $ticket = $this->makeTicket($customer, $team, $project);

        Sanctum::actingAs($agent);

        $this->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $ticket->id);
    }

    private function makeProject(User $admin): Project
    {
        return Project::create([
            'name' => 'Project isolation security test',
            'description' => 'A project used to verify project membership authorization.',
            'status' => 'active',
            'created_by' => $admin->id,
        ]);
    }

    private function makeTicket(User $customer, Team $team, Project $project): Ticket
    {
        return Ticket::create([
            'subject' => 'Project isolation security test',
            'description' => 'A ticket used to verify project membership authorization.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'team_id' => $team->id,
            'project_id' => $project->id,
        ]);
    }
}
