<?php

declare(strict_types=1);

namespace Tests\Unit\Communications\Application\Queries;

use App\Communications\Application\Contracts\NotificationInboxRepository;
use App\Communications\Application\Queries\GetNotificationInbox\GetNotificationInboxHandler;
use App\Communications\Application\Queries\GetNotificationInbox\GetNotificationInboxQuery;
use App\Communications\Application\Queries\GetRecentNotifications\GetRecentNotificationsHandler;
use App\Communications\Application\Queries\GetRecentNotifications\GetRecentNotificationsQuery;
use PHPUnit\Framework\TestCase;

final class NotificationInboxHandlersTest extends TestCase
{
    public function test_inbox_handler_delegates_user_filter_and_clamps_page_size(): void
    {
        $expected = ['notifications' => 'paginator', 'unread_count' => 4];
        $repository = $this->createMock(NotificationInboxRepository::class);
        $repository->expects(self::once())
            ->method('paginate')
            ->with(12, true, 100)
            ->willReturn($expected);

        self::assertSame(
            $expected,
            (new GetNotificationInboxHandler($repository))->handle(
                new GetNotificationInboxQuery(12, true, 500),
            ),
        );
    }

    public function test_recent_handler_delegates_user_and_clamps_limit(): void
    {
        $expected = ['notifications' => 'collection', 'unread_count' => 2];
        $repository = $this->createMock(NotificationInboxRepository::class);
        $repository->expects(self::once())
            ->method('recent')
            ->with(9, 50)
            ->willReturn($expected);

        self::assertSame(
            $expected,
            (new GetRecentNotificationsHandler($repository))->handle(
                new GetRecentNotificationsQuery(9, 100),
            ),
        );
    }
}
