<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketSatisfactionSurvey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerPortalAndCsatTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_customer_portal_only_lists_the_authenticated_customers_tickets(): void
    {
        $customer = $this->customer();
        $otherCustomer = $this->customer();

        $ownTicket = Ticket::query()->create([
            'subject' => 'My request', 'description' => 'My issue',
            'status' => 'open', 'priority' => 'normal', 'user_id' => $customer->id,
        ]);
        Ticket::query()->create([
            'subject' => 'Private request', 'description' => 'Another customer issue',
            'status' => 'open', 'priority' => 'normal', 'user_id' => $otherCustomer->id,
        ]);

        $this->actingAs($customer)->get(route('portal.home'))
            ->assertOk()->assertSee($ownTicket->subject)->assertDontSee('Private request');
    }

    public function test_customer_can_rate_a_resolved_ticket_once(): void
    {
        $customer = $this->customer();
        $ticket = Ticket::query()->create([
            'subject' => 'Resolved request', 'description' => 'Resolved issue',
            'status' => 'resolved', 'priority' => 'normal', 'user_id' => $customer->id,
        ]);

        $this->actingAs($customer)->post(route('portal.satisfaction.store', $ticket), [
            'rating' => 5,
            'comment' => 'Excellent support.',
        ])->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_satisfaction_surveys', [
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Excellent support.',
        ]);

        $this->actingAs($customer)->post(route('portal.satisfaction.store', $ticket), [
            'rating' => 4,
        ])->assertStatus(409);

        $this->assertSame(1, TicketSatisfactionSurvey::query()->where('ticket_id', $ticket->id)->count());
    }

    public function test_customer_cannot_rate_another_customers_ticket(): void
    {
        $customer = $this->customer();
        $owner = $this->customer();
        $ticket = Ticket::query()->create([
            'subject' => 'Private resolved request', 'description' => 'Private issue',
            'status' => 'closed', 'priority' => 'normal', 'user_id' => $owner->id,
        ]);

        $this->actingAs($customer)->post(route('portal.satisfaction.store', $ticket), [
            'rating' => 5,
        ])->assertNotFound();
    }
}
