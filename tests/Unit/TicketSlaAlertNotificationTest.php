<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Ticket;
use App\Notifications\TicketSlaAlertNotification;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

final class TicketSlaAlertNotificationTest extends TestCase
{
    public function test_sla_alert_uses_in_app_preference_only(): void
    {
        $notification = new TicketSlaAlertNotification(
            new Ticket(['subject' => 'SLA test']),
            'first_response',
            'warning',
            Carbon::parse('2026-11-22 12:00:00'),
        );

        $enabledRecipient = new \stdClass();
        $enabledRecipient->in_app_notifications_enabled = true;
        $enabledRecipient->email_notifications_enabled = false;

        $this->assertSame(['database'], $notification->via($enabledRecipient));

        $disabledRecipient = new \stdClass();
        $disabledRecipient->in_app_notifications_enabled = false;

        $this->assertSame([], $notification->via($disabledRecipient));
    }
}
