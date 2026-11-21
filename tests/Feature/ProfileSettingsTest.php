<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_user_can_change_password_after_confirming_current_password(): void
    {
        $user = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.password.update'), [
                'current_password' => 'password',
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_an_incorrect_current_password(): void
    {
        $user = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'NewSecurePassword123!',
                'password_confirmation' => 'NewSecurePassword123!',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_can_disable_email_notifications_without_disabling_in_app_notifications(): void
    {
        $agent = User::query()->where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::query()->where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($agent)
            ->patch(route('profile.preferences.update'), [
                'email_notifications_enabled' => '0',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $agent->refresh();

        $this->assertFalse($agent->email_notifications_enabled);

        $ticket = Ticket::query()->create([
            'subject' => 'Profile notification preference test',
            'description' => 'A ticket for checking notification channels.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        $notification = new TicketActivityNotification(
            $ticket,
            'Ticket updated',
            'The ticket has been updated.',
        );

        $this->assertSame(['database'], $notification->via($agent));
    }
}
