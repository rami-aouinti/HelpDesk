<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Notifications\TicketActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\TestCase;

class TicketActivityNotificationTest extends TestCase
{
    public function test_ticket_activity_notifications_are_queued_after_commit(): void
    {
        $notification = new TicketActivityNotification(
            new Ticket(['subject' => 'Queue verification']),
            'Ticket updated',
            'The ticket was updated.',
        );

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame('notifications', $notification->queue);
        $this->assertTrue($notification->afterCommit);
        $this->assertSame(['database', 'mail'], $notification->via(new \stdClass()));

        $emailOnlyRecipient = new \\stdClass();
        $emailOnlyRecipient->in_app_notifications_enabled = false;
        $emailOnlyRecipient->email_notifications_enabled = true;

        $this->assertSame(['mail'], $notification->via($emailOnlyRecipient));

        $disabledRecipient = new \\stdClass();
        $disabledRecipient->in_app_notifications_enabled = false;
        $disabledRecipient->email_notifications_enabled = false;

        $this->assertSame([], $notification->via($disabledRecipient));
    }

}
