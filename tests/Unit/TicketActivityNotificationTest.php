<?php

namespace Tests\Unit;

use App\Communications\Domain\Services\NotificationChannelPolicy;
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
    }
    public function test_notification_channel_policy_respects_independent_preferences(): void
    {
        $policy = new NotificationChannelPolicy(inAppEnabled: false, emailEnabled: true);

        $this->assertFalse($policy->shouldDeliverInApp());
        $this->assertTrue($policy->shouldDeliverEmail());

        $disabled = new NotificationChannelPolicy(inAppEnabled: false, emailEnabled: false);

        $this->assertFalse($disabled->shouldDeliverInApp());
        $this->assertFalse($disabled->shouldDeliverEmail());
    }

}
