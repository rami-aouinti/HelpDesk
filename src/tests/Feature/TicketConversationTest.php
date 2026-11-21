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
