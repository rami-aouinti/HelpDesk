<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Communications\Application\Contracts\NotificationDeliveryRepository;
use App\Communications\Domain\Enums\NotificationDeliveryStatus;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class NotificationDeliveryTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_attempts_are_counted_and_status_tracks_the_latest_result(): void
    {
        $repository = app(NotificationDeliveryRepository::class);
        $occurredAt = new DateTimeImmutable('2026-11-22T12:00:00+00:00');

        $repository->recordAttempt(
            'notification-abc',
            'users',
            '42',
            'App\\Notifications\\TicketActivityNotification',
            'mail',
            $occurredAt,
        );

        $repository->recordFailed(
            'notification-abc',
            'users',
            '42',
            'mail',
            'SMTP unavailable',
            $occurredAt,
        );

        $repository->recordAttempt(
            'notification-abc',
            'users',
            '42',
            'App\\Notifications\\TicketActivityNotification',
            'mail',
            $occurredAt,
        );

        $delivery = DB::table('notification_deliveries')
            ->where('notification_id', 'notification-abc')
            ->first();

        self::assertNotNull($delivery);
        self::assertSame(NotificationDeliveryStatus::Pending->value, $delivery->status);
        self::assertSame(2, (int) $delivery->attempts);
        self::assertNull($delivery->last_error);

        $repository->recordSent(
            'notification-abc',
            'users',
            '42',
            'mail',
            $occurredAt,
        );

        $delivery = DB::table('notification_deliveries')
            ->where('notification_id', 'notification-abc')
            ->first();

        self::assertSame(NotificationDeliveryStatus::Sent->value, $delivery->status);
        self::assertNotNull($delivery->sent_at);
        self::assertNull($delivery->last_error);
    }
}
