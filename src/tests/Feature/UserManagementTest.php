<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_create_a_user_with_a_role(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'New Support Agent',
                'email' => 'new.agent@example.test',
                'password' => 'SecurePassword123!',
                'password_confirmation' => 'SecurePassword123!',
                'role' => 'agent',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'new.agent@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole('agent'));
        $this->assertTrue($user->is_active);
    }

    public function test_admin_can_update_a_user_role_without_changing_password(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $passwordHash = $customer->password;

        $this->actingAs($admin)
            ->put(route('users.update', $customer), [
                'name' => $customer->name,
                'email' => $customer->email,
                'role' => 'agent',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertTrue($customer->fresh()->hasRole('agent'));
        $this->assertSame($passwordHash, $customer->fresh()->password);
    }

    public function test_admin_can_deactivate_and_reactivate_another_user(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('users.toggle-active', $agent))
            ->assertRedirect(route('users.index'));

        $this->assertFalse($agent->fresh()->is_active);

        $this->patch(route('users.toggle-active', $agent))
            ->assertRedirect(route('users.index'));

        $this->assertTrue($agent->fresh()->is_active);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($customer)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_administrator_cannot_deactivate_their_own_account(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('users.toggle-active', $admin))
            ->assertForbidden();

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $agent->update(['is_active' => false]);

        $this->post(route('login.store'), [
            'email' => $agent->email,
            'password' => env('HELPDESK_DEMO_PASSWORD', 'password'),
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_authenticated_user_is_logged_out_from_protected_pages(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $agent->update(['is_active' => false]);

        $this->actingAs($agent)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_last_active_administrator_cannot_remove_their_admin_role(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'customer',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }


    public function test_admin_can_search_and_filter_users_with_livewire(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->set('search', 'agent')
            ->set('role', 'agent')
            ->set('status', 'active')
            ->assertSee('agent@helpdesk.test')
            ->assertDontSee('customer@helpdesk.test');
    }

    public function test_admin_can_change_user_status_from_livewire_table(): void
    {
        $admin = User::where('email', 'admin@helpdesk.test')->firstOrFail();
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UsersIndex::class)
            ->call('toggleActive', $agent->id)
            ->assertSee('User status updated successfully.');

        $this->assertFalse($agent->fresh()->is_active);
    }

}
