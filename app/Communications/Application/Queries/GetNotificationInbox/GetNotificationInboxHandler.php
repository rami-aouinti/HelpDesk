<?php

namespace App\Communications\Application\Queries\GetNotificationInbox;

use App\Communications\Application\Contracts\NotificationInboxRepository;

final readonly class GetNotificationInboxHandler
{
    public function __construct(private NotificationInboxRepository $notifications)
    {
    }

    /**
     * @return array{notifications: mixed, unread_count: int}
     */
    public function handle(GetNotificationInboxQuery $query): array
    {
        return $this->notifications->paginate(
            $query->userId,
            $query->unreadOnly,
            max(1, min($query->perPage, 100)),
        );
    }
}
