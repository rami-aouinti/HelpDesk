<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->mock(TicketNotificationService::class)
            ->shouldReceive('messageCreated')
            ->zeroOrMoreTimes();
    }

    public function test_customer_can_reply_to_their_own_ticket_and_agent_can_see_the_reply(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);
        $body = 'I have tried the suggested steps, but the issue remains.';

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), ['body' => $body])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'body' => $body,
            'is_internal' => false,
        ]);

        $this->actingAs($agent)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($body);
    }

    public function test_agent_can_add_an_internal_note_that_customer_cannot_see(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);
        $body = 'We are investigating this with the infrastructure team.';

        $this->actingAs($agent)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => $body,
                'is_internal' => true,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => $body,
            'is_internal' => true,
        ]);

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee($body);

        $this->actingAs($agent)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($body);
    }

    public function test_customer_cannot_create_an_internal_note_even_with_a_forged_request(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);
        $body = 'This must not become an internal note.';

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => $body,
                'is_internal' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => $body,
        ]);
    }

    public function test_customer_cannot_reply_to_another_customers_ticket(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherCustomer = User::factory()->create();
        $ticket = $this->makeTicket($otherCustomer);
        $body = 'I should not be able to post here.';

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), ['body' => $body])
            ->assertForbidden();

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => $body,
        ]);
    }

    private function makeTicket(User $customer): Ticket
    {
        return Ticket::create([
            'subject' => 'Conversation test ticket',
            'description' => 'A ticket used to test customer and agent replies.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);
    }
}
