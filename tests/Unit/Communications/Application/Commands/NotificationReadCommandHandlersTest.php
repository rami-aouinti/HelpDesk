<?php

declare(strict_types=1);

namespace Tests\Unit\Communications\Application\Commands;

use App\Communications\Application\Contracts\NotificationInboxRepository;
use App\Communications\Application\Commands\MarkAllNotificationsAsRead\MarkAllNotificationsAsReadCommand;
use App\Communications\Application\Commands\MarkAllNotificationsAsRead\MarkAllNotificationsAsReadHandler;
use App\Communications\Application\Commands\MarkNotificationAsRead\MarkNotificationAsReadCommand;
use App\Communications\Application\Commands\MarkNotificationAsRead\MarkNotificationAsReadHandler;
use PHPUnit\Framework\TestCase;

final class NotificationReadCommandHandlersTest extends TestCase
{
    public function test_mark_notification_as_read_delegates_user_and_notification_identifiers(): void
    {
        $repository = $this->createMock(NotificationInboxRepository::class);
        $repository->expects(self::once())
            ->method('markAsRead')
            ->with(21, 'notification-uuid');

        (new MarkNotificationAsReadHandler($repository))->handle(
            new MarkNotificationAsReadCommand(21, 'notification-uuid'),
        );
    }

    public function test_mark_all_notifications_as_read_returns_number_of_updated_records(): void
    {
        $repository = $this->createMock(NotificationInboxRepository::class);
        $repository->expects(self::once())
            ->method('markAllAsRead')
            ->with(34)
            ->willReturn(5);

        self::assertSame(
            5,
            (new MarkAllNotificationsAsReadHandler($repository))->handle(
                new MarkAllNotificationsAsReadCommand(34),
            ),
        );
    }
}
