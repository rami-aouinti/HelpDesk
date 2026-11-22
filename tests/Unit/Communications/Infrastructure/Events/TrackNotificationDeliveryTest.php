<?php

declare(strict_types=1);

namespace Tests\Unit\Communications\Infrastructure\Events;

use App\Communications\Application\Contracts\NotificationDeliveryRepository;
use App\Communications\Infrastructure\Events\TrackNotificationDelivery;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TrackNotificationDeliveryTest extends TestCase
{
    public function test_sending_records_a_channel_attempt_for_the_recipient(): void
    {
        $repository = $this->createMock(NotificationDeliveryRepository::class);
        $repository->expects(self::once())
            ->method('recordAttempt')
            ->with('notification-123', 'users', '42', 'App\\Notifications\\ExampleNotification', 'mail', self::isInstanceOf(\DateTimeInterface::class));

        $listener = new TrackNotificationDelivery($repository);
        $listener->sending(new NotificationSending(
            new FakeNotifiable(),
            new FakeNotification(),
            'mail',
        ));
    }

    public function test_sent_marks_the_channel_delivery_as_sent(): void
    {
        $repository = $this->createMock(NotificationDeliveryRepository::class);
        $repository->expects(self::once())
            ->method('recordSent')
            ->with('notification-123', 'users', '42', 'database', self::isInstanceOf(\DateTimeInterface::class));

        (new TrackNotificationDelivery($repository))->sent(new NotificationSent(
            new FakeNotifiable(),
            new FakeNotification(),
            'database',
        ));
    }

    public function test_failed_records_a_safe_exception_description(): void
    {
        $repository = $this->createMock(NotificationDeliveryRepository::class);
        $repository->expects(self::once())
            ->method('recordFailed')
            ->with(
                'notification-123',
                'users',
                '42',
                'mail',
                'RuntimeException: SMTP unavailable',
                self::isInstanceOf(\DateTimeInterface::class),
            );

        (new TrackNotificationDelivery($repository))->failed(new NotificationFailed(
            new FakeNotifiable(),
            new FakeNotification(),
            'mail',
            ['exception' => new RuntimeException('SMTP unavailable')],
        ));
    }
}

final class FakeNotifiable
{
    public function getMorphClass(): string
    {
        return 'users';
    }

    public function getAuthIdentifier(): int
    {
        return 42;
    }
}

final class FakeNotification
{
    public string $id = 'notification-123';
}
