<?php

declare(strict_types=1);

namespace App\Communications\Application\Queries\GetNotificationInbox;

final readonly class GetNotificationInboxQuery
{
    public function __construct(
        public int $userId,
        public bool $unreadOnly = false,
        public int $perPage = 15,
    ) {
    }
}
