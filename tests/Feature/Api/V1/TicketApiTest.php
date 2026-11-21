<?php

namespace Tests\Feature\Api\V1;

use App\Models\Ticket;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_personal_access_token_authenticates_the_versioned_api(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $token = $customer->createToken('api-test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('email', $customer->email);
    }

    public function test_customer_can_create_a_sanctum_token_from_the_versioned_api(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/tokens', ['name' => 'integration-test'])
            ->assertCreated()
            ->assertJsonStructure(['token', 'token_type'])
            ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_ticket_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/tickets')->assertUnauthorized();
    }

    public function test_customer_only_sees_their_own_tickets_in_the_list(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherCustomer = User::factory()->create();
        $ownTicket = $this->makeTicket($customer);
        $this->makeTicket($otherCustomer);

        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/v1/tickets')->assertOk();

        $this->assertSame(
            [$ownTicket->id],
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_customer_cannot_view_another_customers_ticket(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherCustomer = User::factory()->create();
        $ticket = $this->makeTicket($otherCustomer);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/tickets/{$ticket->id}")->assertForbidden();
    }

    public function test_customer_can_create_a_ticket_and_receives_a_resource_response(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/v1/tickets', [
            'subject' => 'API ticket creation',
            'description' => 'Created through the versioned HelpDesk API.',
            'priority' => 'high',
        ])->assertCreated()
            ->assertJsonPath('data.subject', 'API ticket creation')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.customer.id', $customer->id);

        $this->assertDatabaseHas('tickets', [
            'id' => $response->json('data.id'),
            'user_id' => $customer->id,
        ]);
    }

    public function test_customer_cannot_update_a_ticket(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);
        Sanctum::actingAs($customer);

        $this->patchJson("/api/v1/tickets/{$ticket->id}", [
            'priority' => 'urgent',
        ])->assertForbidden();
    }

    public function test_agent_can_update_ticket_status_with_allowed_transition(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket(User::where('email', 'customer@helpdesk.test')->firstOrFail());
        $team = Team::where('name', 'General Support')->firstOrFail();
        $ticket->update(['team_id' => $team->id]);

        Sanctum::actingAs($agent);

        $this->patchJson("/api/v1/tickets/{$ticket->id}", [
            'status' => 'in_progress',
        ])->assertOk()
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_agent_cannot_assign_a_ticket_through_the_api(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();
        $ticket = $this->makeTicket($customer);
        Sanctum::actingAs($agent);

        $this->patchJson("/api/v1/tickets/{$ticket->id}", [
            'team_id' => $team->id,
        ])->assertForbidden();
    }

    public function test_admin_can_assign_a_ticket_through_the_api(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $team = Team::where('name', 'General Support')->firstOrFail();
        $ticket = $this->makeTicket($customer);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/tickets/{$ticket->id}", [
            'team_id' => $team->id,
            'assigned_to' => $agent->id,
        ])->assertOk()
            ->assertJsonPath('data.assigned_agent.id', $agent->id);
    }

    public function test_api_validation_errors_use_json_format(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        Sanctum::actingAs($customer);

        $this->postJson('/api/v1/tickets', [
            'subject' => 'bad',
            'description' => 'short',
            'priority' => 'critical',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['subject', 'description', 'priority']);
    }

    public function test_customer_does_not_receive_internal_messages_in_api_resource(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);
        $ticket->messages()->create([
            'user_id' => $customer->id,
            'body' => 'Public message',
            'is_internal' => false,
        ]);
        $ticket->messages()->create([
            'user_id' => User::where('email', 'agent@helpdesk.test')->value('id'),
            'body' => 'Confidential internal note',
            'is_internal' => true,
        ]);
        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonFragment(['body' => 'Public message'])
            ->assertJsonMissing(['body' => 'Confidential internal note']);
    }

    private function makeTicket(User $customer): Ticket
    {
        return Ticket::create([
            'subject' => 'API test ticket',
            'description' => 'A ticket created for API authorization tests.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);
    }
}
