<?php

namespace App\Communications\Application\Commands\MarkNotificationAsRead;

use App\Communications\Application\Contracts\NotificationInboxRepository;

final readonly class MarkNotificationAsReadHandler
{
    public function __construct(private NotificationInboxRepository $notifications)
    {
    }

    public function handle(MarkNotificationAsReadCommand $command): void
    {
        $this->notifications->markAsRead($command->userId, $command->notificationId);
    }
}
