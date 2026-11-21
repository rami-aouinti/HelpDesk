<?php

namespace Tests\Feature;

use App\Livewire\Sprints\Index;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SprintManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_a_sprint_with_a_valid_date_range(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        Livewire::actingAs($admin)->test(Index::class)
            ->call('create')
            ->set('name', 'Sprint Alpha')
            ->set('description', 'Deliver the first customer portal increment.')
            ->set('startDate', '2026-12-01')
            ->set('endDate', '2026-12-14')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sprints', [
            'name' => 'Sprint Alpha',
            'created_by' => $admin->id,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-14',
        ]);
    }


    public function test_admin_can_update_sprint_details_and_dates(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint($admin);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('edit', $sprint->id)
            ->set('name', 'Updated sprint name')
            ->set('description', 'Updated sprint goal.')
            ->set('startDate', '2026-12-03')
            ->set('endDate', '2026-12-17')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sprints', [
            'id' => $sprint->id,
            'name' => 'Updated sprint name',
            'description' => 'Updated sprint goal.',
            'start_date' => '2026-12-03',
            'end_date' => '2026-12-17',
        ]);
    }

    public function test_agent_cannot_edit_a_sprint(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint($admin);

        Livewire::actingAs($agent)->test(Index::class)
            ->call('edit', $sprint->id)
            ->assertForbidden();
    }

    public function test_sprint_end_date_cannot_be_before_start_date(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        Livewire::actingAs($admin)->test(Index::class)
            ->call('create')
            ->set('name', 'Invalid sprint')
            ->set('startDate', '2026-12-15')
            ->set('endDate', '2026-12-01')
            ->call('save')
            ->assertHasErrors(['endDate']);
    }

    public function test_only_admin_can_create_or_edit_sprints(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        Livewire::actingAs($agent)->test(Index::class)
            ->call('create')
            ->assertForbidden();
    }

    public function test_sprint_cannot_be_deleted_while_it_contains_tickets(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint($admin);
        $this->makeTicket($customer, $sprint);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('delete', $sprint->id)
            ->assertSee('Move all tickets out of this sprint before deleting it.');

        $this->assertDatabaseHas('sprints', ['id' => $sprint->id]);
    }

    public function test_admin_can_delete_an_empty_sprint(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $sprint = $this->makeSprint($admin);

        Livewire::actingAs($admin)->test(Index::class)
            ->call('delete', $sprint->id);

        $this->assertDatabaseMissing('sprints', ['id' => $sprint->id]);
    }

    public function test_sprint_is_done_only_when_it_has_tickets_and_all_are_closed(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $emptySprint = $this->makeSprint($admin);
        $this->assertFalse($emptySprint->fresh()->is_completed);

        $sprint = $this->makeSprint($admin, ['name' => 'Sprint with work']);
        $first = $this->makeTicket($customer, $sprint);
        $second = $this->makeTicket($customer, $sprint, ['subject' => 'Second sprint ticket']);

        $first->update(['status' => 'closed']);
        $this->assertFalse($sprint->fresh()->is_completed);

        $second->update(['status' => 'closed']);
        $this->assertTrue($sprint->fresh()->is_completed);
    }



    public function test_admin_can_open_sprint_management_and_kanban_pages(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('sprints.index'))
            ->assertOk()
            ->assertSee('All sprints');

        $this->actingAs($admin)
            ->get(route('kanban.index'))
            ->assertOk()
            ->assertSee('No sprint available');
    }

    public function test_customer_cannot_access_sprint_planning(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        Livewire::actingAs($customer)->test(Index::class)
            ->assertForbidden();
    }

    private function makeSprint(User $creator, array $attributes = []): Sprint
    {
        return Sprint::create([
            'name' => 'Sprint '.uniqid(),
            'description' => 'Sprint test fixture.',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-14',
            'created_by' => $creator->id,
            ...$attributes,
        ]);
    }

    private function makeTicket(User $customer, Sprint $sprint, array $attributes = []): Ticket
    {
        return Ticket::create([
            'subject' => 'Sprint ticket',
            'description' => 'Ticket used for sprint management tests.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
            'sprint_id' => $sprint->id,
            ...$attributes,
        ]);
    }
}
