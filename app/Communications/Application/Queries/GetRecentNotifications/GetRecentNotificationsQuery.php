<?php

declare(strict_types=1);

namespace App\Communications\Application\Queries\GetRecentNotifications;

final readonly class GetRecentNotificationsQuery
{
    public function __construct(
        public int $userId,
        public int $limit = 8,
    ) {
    }
}
