<?php

namespace Tests\\Feature;

use App\\Livewire\\Teams\\Index as TeamsIndex;
use App\\Models\\Team;
use App\\Models\\Ticket;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Livewire\\Livewire;
use Tests\\TestCase;

class TeamsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_a_team_with_active_agents(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(TeamsIndex::class)
            ->call('create')
            ->set('name', 'Technical Support')
            ->set('description', 'Handles technical issues.')
            ->set('members', [$agent->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Team created successfully.');

        $team = Team::query()->where('name', 'Technical Support')->firstOrFail();

        $this->assertTrue($team->members()->whereKey($agent->id)->exists());
    }

    public function test_admin_can_update_team_and_members(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $team = Team::query()->create(['name' => 'Old Team']);

        Livewire::actingAs($admin)
            ->test(TeamsIndex::class)
            ->call('edit', $team->id)
            ->set('name', 'Customer Support')
            ->set('members', [$agent->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Customer Support']);
        $this->assertTrue($team->fresh()->members()->whereKey($agent->id)->exists());
    }

    public function test_only_active_agents_can_be_added_to_a_team(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(TeamsIndex::class)
            ->call('create')
            ->set('name', 'Invalid Team')
            ->set('members', [$customer->id])
            ->call('save')
            ->assertHasErrors('members');

        $this->assertDatabaseMissing('teams', ['name' => 'Invalid Team']);
    }

    public function test_non_admin_cannot_access_team_management(): void
    {
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($customer)
            ->get(route('teams.index'))
            ->assertForbidden();
    }

    public function test_team_with_tickets_cannot_be_deleted(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        $team = Team::query()->create(['name' => 'Billing Support']);

        Ticket::query()->create([
            'subject' => 'Payment issue',
            'description' => 'Payment was declined.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $admin->id,
            'team_id' => $team->id,
        ]);

        Livewire::actingAs($admin)
            ->test(TeamsIndex::class)
            ->call('delete', $team->id)
            ->assertHasErrors('team');

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }
}
