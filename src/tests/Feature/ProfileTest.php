<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    public function test_authenticated_user_can_view_profile_and_help_pages(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('My profile')
            ->assertSee($user->email);

        $this->get(route('help'))
            ->assertOk()
            ->assertSee('Help center')
            ->assertSee('Internal notes')
            ->assertSee('Attachments');
    }

    public function test_user_can_update_their_name_and_profile_photo(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Updated Customer Name',
                'email' => $user->email,
                'profile_photo' => UploadedFile::fake()->createWithContent(
                    'avatar.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jX2sAAAAASUVORK5CYII=')
                ),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Updated Customer Name', $user->name);
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_changing_email_requires_reverification(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => 'new.customer@helpdesk.test',
            ])
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new.customer@helpdesk.test',
            'email_verified_at' => null,
        ]);
    }

    public function test_user_cannot_use_an_email_address_owned_by_another_account(): void
    {
        $user = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => $user->name,
                'email' => 'admin@helpdesk.test',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('email');

        $this->assertSame('customer@helpdesk.test', $user->fresh()->email);
    }
}
