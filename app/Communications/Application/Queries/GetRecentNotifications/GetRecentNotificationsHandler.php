<?php

declare(strict_types=1);

namespace App\Communications\Application\Queries\GetRecentNotifications;

use App\Communications\Application\Contracts\NotificationInboxRepository;

final readonly class GetRecentNotificationsHandler
{
    public function __construct(private NotificationInboxRepository $notifications)
    {
    }

    /**
     * @return array{notifications: mixed, unread_count: int}
     */
    public function handle(GetRecentNotificationsQuery $query): array
    {
        return $this->notifications->recent($query->userId, max(1, min($query->limit, 50)));
    }
}
