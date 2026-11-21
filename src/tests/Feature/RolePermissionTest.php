<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_has_user_team_and_global_ticket_permissions(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Team::class));
        $this->assertTrue($admin->can('tickets.view.all'));
        $this->assertTrue($admin->can('tickets.update'));
    }

    public function test_agent_can_process_tickets_but_cannot_manage_users_or_teams(): void
    {
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = $this->makeTicket($customer);

        $this->assertTrue(Gate::forUser($agent)->allows('viewAny', \App\Models\Ticket::class));
        $this->assertTrue(Gate::forUser($agent)->allows('view', $ticket));
        $this->assertTrue(Gate::forUser($agent)->allows('update', $ticket));
        $this->assertFalse(Gate::forUser($agent)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($agent)->allows('viewAny', Team::class));
    }

    public function test_customer_can_access_only_their_own_tickets(): void
    {
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $ownTicket = $this->makeTicket($customer);
        $otherTicket = $this->makeTicket($otherCustomer);

        $this->assertTrue(Gate::forUser($customer)->allows('viewAny', \App\Models\Ticket::class));
        $this->assertTrue(Gate::forUser($customer)->allows('create', \App\Models\Ticket::class));
        $this->assertTrue(Gate::forUser($customer)->allows('view', $ownTicket));
        $this->assertFalse(Gate::forUser($customer)->allows('view', $otherTicket));
        $this->assertFalse(Gate::forUser($customer)->allows('update', $ownTicket));
        $this->assertFalse(Gate::forUser($customer)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($customer)->allows('viewAny', Team::class));
    }

    public function test_only_administrators_can_open_user_and_team_management_pages(): void
    {
        $admin = User::query()->where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($agent)->get(route('users.index'))->assertForbidden();
        $this->actingAs($agent)->get(route('teams.index'))->assertForbidden();

        $this->actingAs($customer)->get(route('users.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('teams.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('teams.index'))->assertOk();
    }

    public function test_ticket_list_is_scoped_to_the_customer_but_visible_globally_to_agents(): void
    {
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();
        $otherCustomer = User::factory()->create();
        $otherCustomer->assignRole('customer');

        $ownTicket = $this->makeTicket($customer);
        $ownTicket->update(['subject' => 'My private ticket']);
        $otherTicket = $this->makeTicket($otherCustomer);
        $otherTicket->update(['subject' => 'Another customer ticket']);

        $this->actingAs($customer)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('My private ticket')
            ->assertDontSee('Another customer ticket');

        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();

        $this->actingAs($agent)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('My private ticket')
            ->assertSee('Another customer ticket');
    }

    private function makeTicket(User $customer): Ticket
    {
        return Ticket::query()->create([
            'subject' => 'Permission test ticket',
            'description' => 'A ticket used to verify role-based access.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);
    }
}
