<?php

namespace App\Communications\Infrastructure\Persistence\Eloquent;

use App\Communications\Application\Contracts\NotificationInboxRepository;
use App\Models\User;

final class EloquentNotificationInboxRepository implements NotificationInboxRepository
{
    public function paginate(int $userId, bool $unreadOnly, int $perPage): array
    {
        $user = User::query()->findOrFail($userId);
        $query = $user->notifications()->latest();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return [
            'notifications' => $query->paginate($perPage)->withQueryString(),
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }

    public function recent(int $userId, int $limit): array
    {
        $user = User::query()->findOrFail($userId);

        return [
            'notifications' => $user->notifications()->latest()->limit($limit)->get(),
            'unread_count' => $user->unreadNotifications()->count(),
        ];
    }
    public function markAsRead(int $userId, string $notificationId): void
    {
        $user = User::query()->findOrFail($userId);

        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();
    }

    public function markAllAsRead(int $userId): int
    {
        $user = User::query()->findOrFail($userId);

        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
