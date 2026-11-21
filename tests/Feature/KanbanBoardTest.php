<?php

namespace Tests\Feature;

use App\Livewire\Kanban\Board;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KanbanBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_agent_can_filter_kanban_to_only_tickets_assigned_to_them(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $otherAgent = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint();
        $mine = $this->makeTicket($customer, $sprint, ['subject' => 'My assigned ticket', 'assigned_to' => $agent->id]);
        $others = $this->makeTicket($customer, $sprint, ['subject' => 'Another agent ticket', 'assigned_to' => $otherAgent->id]);

        Livewire::actingAs($agent)->test(Board::class)
            ->assertSee('My assigned ticket')
            ->assertSee('Another agent ticket')
            ->set('onlyMine', true)
            ->assertSee('My assigned ticket')
            ->assertDontSee('Another agent ticket');
    }

    public function test_agent_can_move_only_their_assigned_ticket_to_an_allowed_status(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint();
        $ticket = $this->makeTicket($customer, $sprint, ['assigned_to' => $agent->id]);

        Livewire::actingAs($agent)->test(Board::class)
            ->call('moveTicketStatus', $ticket->id, 'in_progress')
            ->assertHasNoErrors();

        $this->assertSame('in_progress', $ticket->fresh()->status);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'status',
            'new_value' => 'in_progress',
        ]);
    }

    public function test_agent_cannot_move_another_agents_ticket_between_columns(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $otherAgent = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint();
        $ticket = $this->makeTicket($customer, $sprint, ['assigned_to' => $otherAgent->id]);

        Livewire::actingAs($agent)->test(Board::class)
            ->call('moveTicketStatus', $ticket->id, 'in_progress')
            ->assertForbidden();

        $this->assertSame('open', $ticket->fresh()->status);
        $this->assertSame(0, TicketHistory::where('ticket_id', $ticket->id)->count());
    }

    public function test_agent_cannot_move_another_agents_ticket_to_another_sprint(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $otherAgent = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $current = $this->makeSprint();
        $destination = $this->makeSprint(['name' => 'Destination sprint']);
        $ticket = $this->makeTicket($customer, $current, ['assigned_to' => $otherAgent->id]);

        Livewire::actingAs($agent)->test(Board::class)
            ->set('sprintId', $current->id)
            ->call('moveTicketToSprint', $ticket->id, $destination->id)
            ->assertForbidden();

        $this->assertSame($current->id, $ticket->fresh()->sprint_id);
    }

    public function test_agent_can_move_their_ticket_to_another_sprint(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $current = $this->makeSprint();
        $destination = $this->makeSprint(['name' => 'Next sprint']);
        $ticket = $this->makeTicket($customer, $current, ['assigned_to' => $agent->id]);

        Livewire::actingAs($agent)->test(Board::class)
            ->call('moveTicketToSprint', $ticket->id, $destination->id)
            ->assertHasNoErrors();

        $this->assertSame($destination->id, $ticket->fresh()->sprint_id);
        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'action' => 'sprint_id',
            'old_value' => (string) $current->id,
            'new_value' => (string) $destination->id,
        ]);
    }

    public function test_admin_can_move_any_ticket_on_the_board(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $otherAgent = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint();
        $ticket = $this->makeTicket($customer, $sprint, ['assigned_to' => $otherAgent->id]);

        Livewire::actingAs($admin)->test(Board::class)
            ->call('moveTicketStatus', $ticket->id, 'closed')
            ->assertHasNoErrors();

        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_ticket_from_another_sprint_cannot_be_moved_through_current_board(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $current = $this->makeSprint();
        $other = $this->makeSprint(['name' => 'Other sprint']);
        $ticket = $this->makeTicket($customer, $other);

        Livewire::actingAs($admin)->test(Board::class)
            ->set('sprintId', $current->id)
            ->call('moveTicketStatus', $ticket->id, 'closed')
            ->assertNotFound();

        $this->assertSame('open', $ticket->fresh()->status);
    }


    public function test_admin_can_move_any_ticket_to_another_sprint(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $otherAgent = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $current = $this->makeSprint();
        $destination = $this->makeSprint(['name' => 'Admin destination']);
        $ticket = $this->makeTicket($customer, $current, ['assigned_to' => $otherAgent->id]);

        Livewire::actingAs($admin)->test(Board::class)
            ->set('sprintId', $current->id)
            ->call('moveTicketToSprint', $ticket->id, $destination->id)
            ->assertHasNoErrors();

        $this->assertSame($destination->id, $ticket->fresh()->sprint_id);
    }

    public function test_ticket_cannot_be_moved_into_a_completed_sprint(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $current = $this->makeSprint();
        $completed = $this->makeSprint(['name' => 'Already completed']);
        $closedTicket = $this->makeTicket($customer, $completed, ['status' => 'closed']);
        $ticket = $this->makeTicket($customer, $current, ['assigned_to' => $agent->id]);

        $this->assertTrue($completed->fresh()->is_completed);

        Livewire::actingAs($admin)->test(Board::class)
            ->set('sprintId', $current->id)
            ->call('moveTicketToSprint', $ticket->id, $completed->id)
            ->assertHasErrors('board');

        $this->assertSame($current->id, $ticket->fresh()->sprint_id);
    }


    public function test_agent_can_add_their_assigned_backlog_ticket_to_a_sprint(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint();
        $ticket = Ticket::create([
            'subject' => 'Backlog ticket assigned to me',
            'description' => 'An unscheduled ticket that I own.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'assigned_to' => $agent->id,
        ]);

        Livewire::actingAs($agent)->test(Board::class)
            ->call('addTicketToSprint', $ticket->id)
            ->assertHasNoErrors();

        $this->assertSame($sprint->id, $ticket->fresh()->sprint_id);
    }

    public function test_agent_cannot_add_another_agents_backlog_ticket_to_a_sprint(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $otherAgent = User::where('email', 'sarah.agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint();
        $ticket = Ticket::create([
            'subject' => 'Backlog ticket assigned elsewhere',
            'description' => 'An unscheduled ticket owned by another agent.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'assigned_to' => $otherAgent->id,
        ]);

        Livewire::actingAs($agent)->test(Board::class)
            ->call('addTicketToSprint', $ticket->id)
            ->assertForbidden();

        $this->assertNull($ticket->fresh()->sprint_id);
        $this->assertDatabaseHas('sprints', ['id' => $sprint->id]);
    }

    private function makeSprint(array $attributes = []): Sprint
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        return Sprint::create([
            'name' => 'Sprint '.uniqid(),
            'description' => null,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-14',
            'created_by' => $admin->id,
            ...$attributes,
        ]);
    }

    private function makeTicket(User $customer, Sprint $sprint, array $attributes = []): Ticket
    {
        return Ticket::create([
            'subject' => 'Kanban ticket',
            'description' => 'Ticket used for Kanban board tests.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'sprint_id' => $sprint->id,
            ...$attributes,
        ]);
    }
}
