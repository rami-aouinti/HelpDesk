<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_remaining_time_is_calculated_from_estimate_and_elapsed_minutes(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Time tracking test',
            'description' => 'A ticket used to test time tracking calculations.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'estimated_minutes' => 300,
            'elapsed_minutes' => 120,
        ]);

        $this->assertSame(180, $ticket->remaining_minutes);
    }

    public function test_remaining_time_never_becomes_negative(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Overtime tracking test',
            'description' => 'Elapsed time can exceed the initial estimate.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'estimated_minutes' => 120,
            'elapsed_minutes' => 180,
        ]);

        $this->assertSame(0, $ticket->remaining_minutes);
    }

    public function test_agent_can_update_estimated_and_elapsed_time_values(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Update time tracking',
            'description' => 'A ticket used to test updating time values.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'estimated_minutes' => 300,
            'elapsed_minutes' => 0,
        ]);

        app(TicketService::class)->update($ticket, [
            'estimated_minutes' => 300,
            'elapsed_minutes' => 120,
        ]);

        $this->assertSame(300, $ticket->fresh()->estimated_minutes);
        $this->assertSame(120, $ticket->fresh()->elapsed_minutes);
        $this->assertSame(180, $ticket->fresh()->remaining_minutes);
    }
}
