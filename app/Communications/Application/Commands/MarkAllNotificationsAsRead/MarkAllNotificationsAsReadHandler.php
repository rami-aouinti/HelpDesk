<?php

declare(strict_types=1);

namespace App\Communications\Application\Commands\MarkAllNotificationsAsRead;

use App\Communications\Application\Contracts\NotificationInboxRepository;

final readonly class MarkAllNotificationsAsReadHandler
{
    public function __construct(private NotificationInboxRepository $notifications)
    {
    }

    public function handle(MarkAllNotificationsAsReadCommand $command): int
    {
        return $this->notifications->markAllAsRead($command->userId);
    }
}
