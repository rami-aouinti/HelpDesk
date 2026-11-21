<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketClaimConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_a_second_claim_using_a_stale_ticket_model_cannot_overwrite_the_first_agent(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $firstAgent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $secondAgent = User::where('email', 'amira.agent@helpdesk.test')->firstOrFail();

        $team = Team::create([
            'name' => 'Concurrent Claim Test',
            'description' => 'Shared team for ticket claim race protection.',
        ]);
        $team->members()->attach([$firstAgent->id, $secondAgent->id]);

        $ticket = Ticket::create([
            'subject' => 'Concurrent assignment request',
            'description' => 'Only one agent may claim this ticket.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $service = app(TicketService::class);
        $service->claim($ticket, $firstAgent, $team->id);

        $this->assertSame($firstAgent->id, $ticket->fresh()->assigned_to);

        try {
            $service->claim($ticket, $secondAgent, $team->id);
            $this->fail('A ticket must not be claimed by a second agent.');
        } catch (ValidationException) {
            $this->assertSame($firstAgent->id, $ticket->fresh()->assigned_to);
        }
    }
}
