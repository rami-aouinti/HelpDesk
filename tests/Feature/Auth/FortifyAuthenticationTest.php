<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class FortifyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Notification::fake();
    }

    public function test_guest_can_register_and_receives_customer_role_and_verification_email(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'New Customer',
            'email' => 'NEW.CUSTOMER@example.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ]);

        $user = User::where('email', 'new.customer@example.test')->firstOrFail();

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('StrongPassword123!', $user->password));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_rejects_invalid_or_duplicate_data(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Duplicate Customer',
            'email' => 'customer@helpdesk.test',
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ])->assertSessionHasErrors('email');

        $this->post(route('register.store'), [
            'name' => 'Weak Password',
            'email' => 'weak@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.test']);
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => env('HELPDESK_DEMO_PASSWORD', 'password'),
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_normalizes_email_case_and_whitespace(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->post(route('login.store'), [
            'email' => '  CUSTOMER@HELPDESK.TEST  ',
            'password' => env('HELPDESK_DEMO_PASSWORD', 'password'),
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_invalid_password_and_unknown_email(): void
    {
        $this->post(route('login.store'), [
            'email' => 'customer@helpdesk.test',
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => 'unknown@example.test',
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        $user = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $user->update(['is_active' => false]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => env('HELPDESK_DEMO_PASSWORD', 'password'),
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_unverified_user_is_redirected_to_email_verification_screen(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_user_can_verify_email_with_valid_signed_link(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $user->forceFill(['email_verified_at' => null])->save();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect('/dashboard');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_email_verification_rejects_invalid_signature(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)
            ->get(route('verification.verify', [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]))
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_user_can_request_another_verification_email(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $user->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_guest_can_request_password_reset_link(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_link_is_not_sent_for_unknown_email(): void
    {
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('BrandNewPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_password_reset_rejects_invalid_token(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $originalHash = $user->password;

        $this->post(route('password.update'), [
            'token' => Str::random(64),
            'email' => $user->email,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ])->assertSessionHasErrors('email');

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_password_reset_rejects_password_confirmation_mismatch(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $token = Password::broker()->createToken($user);
        $originalHash = $user->password;

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ])->assertSessionHasErrors('password');

        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_email_verification_rejects_a_valid_signature_with_wrong_email_hash(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $user->forceFill(['email_verified_at' => null])->save();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), ['id' => $user->id, 'hash' => sha1('different@example.test')]);
        $this->actingAs($user)->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_password_reset_rejects_expired_token(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $token = Password::broker()->createToken($user);
        $this->travel(61)->minutes();
        $this->post(route('password.update'), ['token' => $token, 'email' => $user->email, 'password' => 'BrandNewPassword123!', 'password_confirmation' => 'BrandNewPassword123!'])->assertSessionHasErrors('email');
        $this->assertFalse(Hash::check('BrandNewPassword123!', $user->fresh()->password));
    }
}
