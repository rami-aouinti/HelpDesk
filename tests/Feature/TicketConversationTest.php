<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }


    public function test_customer_can_reply_to_their_own_ticket(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'I have tried the suggested steps, but the issue remains.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'body' => 'I have tried the suggested steps, but the issue remains.',
            'is_internal' => false,
        ]);
    }

    public function test_agent_can_add_an_internal_note_and_customer_cannot_see_it(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($agent)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'We are investigating this with the infrastructure team.',
                'is_internal' => true,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('We are investigating this with the infrastructure team.');

        $this->actingAs($agent)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('We are investigating this with the infrastructure team.');
    }

    public function test_customer_cannot_create_an_internal_note(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'This must not become an internal note.',
                'is_internal' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'This must not become an internal note.',
        ]);
    }

    public function test_customer_cannot_reply_to_another_customers_ticket(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherCustomer = User::factory()->create();
        $ticket = $this->makeTicket($otherCustomer);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'body' => 'I should not be able to post here.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'I should not be able to post here.',
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
