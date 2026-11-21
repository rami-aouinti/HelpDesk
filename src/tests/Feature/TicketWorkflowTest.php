<?php

namespace Tests\Feature;

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

        $ticket = Ticket::firstOrFail();
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
        $this->assertSame(0, TicketHistory::count());
    }
}
