<?php

declare(strict_types=1);

namespace App\Communications\Application\Commands\MarkAllNotificationsAsRead;

final readonly class MarkAllNotificationsAsReadCommand
{
    public function __construct(public int $userId)
    {
    }
}
