<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_can_create_and_read_a_company_event(): void
    {
        $admin = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->postJson(route('calendar.events.store'), [
            'title' => 'Company town hall',
            'type' => 'meeting',
            'visibility' => 'company',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
        ])->assertCreated();

        $this->getJson(route('calendar.feed'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Company town hall']);
    }

    public function test_personal_events_are_not_visible_to_other_users(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('agent');

        $this->actingAs($owner)->postJson(route('calendar.events.store'), [
            'title' => 'Private focus time',
            'type' => 'reminder',
            'visibility' => 'personal',
            'starts_at' => now()->addDay()->toDateTimeString(),
        ])->assertCreated();

        $otherUser = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $otherUser->assignRole('agent');

        $this->actingAs($otherUser)->getJson(route('calendar.feed'))
            ->assertOk()
            ->assertJsonMissing(['title' => 'Private focus time']);
    }

    public function test_non_admin_cannot_publish_company_events(): void
    {
        $agent = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $agent->assignRole('agent');

        $this->actingAs($agent)->postJson(route('calendar.events.store'), [
            'title' => 'Everyone event',
            'type' => 'announcement',
            'visibility' => 'company',
            'starts_at' => now()->addDay()->toDateTimeString(),
        ])->assertUnprocessable();
    }
    public function test_only_the_owner_or_an_admin_can_edit_and_delete_an_event(): void
    {
        $owner = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('agent');

        $this->actingAs($owner)->postJson(route('calendar.events.store'), [
            'title' => 'My reminder',
            'type' => 'reminder',
            'visibility' => 'personal',
            'starts_at' => now()->addDay()->toDateTimeString(),
        ])->assertCreated();

        $eventId = \\App\\Models\\CalendarEvent::query()->firstOrFail()->id;
        $otherUser = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $otherUser->assignRole('agent');

        $this->actingAs($otherUser)
            ->patchJson(route('calendar.events.update', $eventId), [
                'title' => 'Not mine',
                'type' => 'reminder',
                'visibility' => 'personal',
                'starts_at' => now()->addDay()->toDateTimeString(),
            ])->assertForbidden();

        $this->actingAs($owner)
            ->patchJson(route('calendar.events.update', $eventId), [
                'title' => 'Updated reminder',
                'type' => 'reminder',
                'visibility' => 'personal',
                'starts_at' => now()->addDay()->toDateTimeString(),
            ])->assertOk();

        $this->deleteJson(route('calendar.events.destroy', $eventId))->assertOk();
    }

}
