<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_customer_account(): void
    {
        $this->seed();

        $response = $this->post(route('register.store'), [
            'name' => 'New Customer',
            'email' => 'new.customer@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ]);

        $user = User::where('email', 'new.customer@example.test')->firstOrFail();

        $response->assertRedirect(route('verification.notice'));
        $this->assertTrue($user->hasRole('customer'));
        $this->assertNull($user->email_verified_at);
    }

    public function test_unverified_users_are_redirected_to_email_verification(): void
    {
        $this->seed();

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_open_the_dashboard(): void
    {
        $this->seed();

        $user = User::where('email', 'admin@helpdesk.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
