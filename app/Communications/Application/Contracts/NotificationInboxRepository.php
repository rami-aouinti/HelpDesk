<?php

namespace App\Communications\Application\Contracts;

interface NotificationInboxRepository
{
    /**
     * @return array{notifications: mixed, unread_count: int}
     */
    public function paginate(int $userId, bool $unreadOnly, int $perPage): array;

    /**
     * @return array{notifications: mixed, unread_count: int}
     */
    public function recent(int $userId, int $limit): array;
}
