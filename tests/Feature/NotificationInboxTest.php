<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketActivityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    public function test_user_can_view_and_mark_only_their_own_notifications_as_read(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $otherUser = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Inbox notification test',
            'description' => 'A ticket used to test the notification inbox.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $otherUser->id,
        ]);

        Notification::sendNow($agent, new TicketActivityNotification(
            $ticket,
            'New ticket created',
            'A new customer ticket needs attention.',
        ));

        Notification::sendNow($otherUser, new TicketActivityNotification(
            $ticket,
            'Private notification',
            'This belongs to another account.',
        ));

        $notification = $agent->notifications()->firstOrFail();

        $this->actingAs($agent)
            ->getJson(route('notifications.recent'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'New ticket created');

        $this->actingAs($agent)
            ->patchJson(route('notifications.read', $notification->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull($agent->notifications()->findOrFail($notification->id)->read_at);
        $this->assertNull($otherUser->notifications()->firstOrFail()->read_at);
    }

    public function test_user_can_open_their_notification_inbox_and_filter_unread_items(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();
        $ticket = Ticket::create([
            'subject' => 'Notification inbox page test',
            'description' => 'A ticket used to test the inbox page.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        Notification::sendNow($agent, new TicketActivityNotification($ticket, 'Unread update', 'This update is unread.'));
        Notification::sendNow($agent, new TicketActivityNotification($ticket, 'Read update', 'This update is already read.'));
        $agent->notifications()->where('data->title', 'Read update')->firstOrFail()->markAsRead();
        Notification::sendNow($customer, new TicketActivityNotification($ticket, 'Private update', 'This belongs to the customer.'));

        $this->actingAs($agent)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Unread update')
            ->assertSee('Read update')
            ->assertDontSee('Private update');

        $this->actingAs($agent)
            ->get(route('notifications.index', ['filter' => 'unread']))
            ->assertOk()
            ->assertSee('Unread update')
            ->assertDontSee('Read update');
    }

    public function test_user_can_mark_all_their_notifications_as_read(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Mark all inbox notifications read',
            'description' => 'A ticket used to test marking notifications as read.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        Notification::sendNow($agent, new TicketActivityNotification($ticket, 'Update one', 'First update.'));
        Notification::sendNow($agent, new TicketActivityNotification($ticket, 'Update two', 'Second update.'));

        $this->actingAs($agent)
            ->patchJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertSame(0, $agent->unreadNotifications()->count());
    }
    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $agent = User::where('email', 'agent@helpdesk.test')->firstOrFail();
        $customer = User::where('email', 'customer@helpdesk.test')->firstOrFail();

        $ticket = Ticket::create([
            'subject' => 'Notification ownership test',
            'description' => 'A ticket used to verify notification ownership.',
            'status' => 'open',
            'priority' => 'normal',
            'user_id' => $customer->id,
        ]);

        Notification::sendNow($customer, new TicketActivityNotification(
            $ticket,
            'Customer-only notification',
            'This notification must not be accessible to the agent.',
        ));

        $notification = $customer->notifications()->firstOrFail();

        $this->actingAs($agent)
            ->patchJson(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($customer->notifications()->findOrFail($notification->id)->read_at);
    }

}
