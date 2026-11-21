<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketTeamIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_agent_api_list_only_contains_tickets_from_its_own_team_and_unassigned_tickets(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ownTeam = Team::where('name', 'General Support')->firstOrFail();
        $foreignTeam = Team::create([
            'name' => 'Restricted Support',
            'description' => 'A team outside the current agent scope.',
        ]);

        $ownTicket = $this->makeTicket($customer, ['team_id' => $ownTeam->id]);
        $foreignTicket = $this->makeTicket($customer, ['team_id' => $foreignTeam->id]);
        $unassignedTicket = $this->makeTicket($customer);

        Sanctum::actingAs($agent);

        $response = $this->getJson('/api/v1/tickets')->assertOk();

        $visibleIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($ownTicket->id, $visibleIds);
        $this->assertContains($unassignedTicket->id, $visibleIds);
        $this->assertNotContains($foreignTicket->id, $visibleIds);
    }

    public function test_agent_cannot_read_a_ticket_assigned_to_another_team_through_the_api(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $foreignTeam = Team::create([
            'name' => 'Confidential Support',
            'description' => 'A team the current agent does not belong to.',
        ]);
        $ticket = $this->makeTicket($customer, ['team_id' => $foreignTeam->id]);

        Sanctum::actingAs($agent);

        $this->getJson("/api/v1/tickets/{$ticket->id}")->assertForbidden();
    }

    public function test_agent_cannot_post_a_message_to_a_ticket_outside_its_team_scope(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $foreignTeam = Team::create([
            'name' => 'Private Escalations',
            'description' => 'A team the current agent does not belong to.',
        ]);
        $ticket = $this->makeTicket($customer, ['team_id' => $foreignTeam->id]);

        Sanctum::actingAs($agent);

        $this->postJson("/api/v1/tickets/{$ticket->id}/messages", [
            'body' => 'This message must not be persisted.',
        ])->assertForbidden();

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'This message must not be persisted.',
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeTicket(User $customer, array $attributes = []): Ticket
    {
        return Ticket::create([
            'subject' => 'Team isolation security test',
            'description' => 'A ticket used to verify support team authorization boundaries.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            ...$attributes,
        ]);
    }
}
