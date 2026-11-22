<?php

namespace App\Communications\Application\Commands\MarkNotificationAsRead;

final readonly class MarkNotificationAsReadCommand
{
    public function __construct(
        public int $userId,
        public string $notificationId,
    ) {
    }
}
