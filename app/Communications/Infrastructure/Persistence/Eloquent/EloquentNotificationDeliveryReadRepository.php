<?php

declare(strict_types=1);

namespace App\Communications\Infrastructure\Persistence\Eloquent;

use App\Communications\Application\Contracts\NotificationDeliveryReadRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentNotificationDeliveryReadRepository implements NotificationDeliveryReadRepository
{
    public function paginate(array $filters, int $perPage): mixed
    {
        return $this->filteredQuery($filters)
            ->select([
                'notification_deliveries.id',
                'notification_deliveries.notification_id',
                'notification_deliveries.notifiable_type',
                'notification_deliveries.notifiable_id',
                'notification_deliveries.notification_type',
                'notification_deliveries.channel',
                'notification_deliveries.status',
                'notification_deliveries.attempts',
                'notification_deliveries.last_error',
                'notification_deliveries.last_attempt_at',
                'notification_deliveries.sent_at',
                'notification_deliveries.failed_at',
                'notification_deliveries.created_at',
                'users.name as recipient_name',
                'users.email as recipient_email',
            ])
            ->orderByDesc('notification_deliveries.created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function summary(array $filters): array
    {
        $row = $this->filteredQuery($filters)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN notification_deliveries.status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN notification_deliveries.status = 'sent' THEN 1 ELSE 0 END) as sent")
            ->selectRaw("SUM(CASE WHEN notification_deliveries.status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'pending' => (int) ($row->pending ?? 0),
            'sent' => (int) ($row->sent ?? 0),
            'failed' => (int) ($row->failed ?? 0),
        ];
    }

    /** @param array{status?: string, channel?: string, search?: string, from?: string, to?: string} $filters */
    private function filteredQuery(array $filters): Builder
    {
        $query = DB::table('notification_deliveries')
            ->leftJoin('users', function ($join): void {
                $join->on('users.id', '=', 'notification_deliveries.notifiable_id')
                    ->whereIn('notification_deliveries.notifiable_type', [
                        'App\\Models\\User',
                        'users',
                    ]);
            });

        if (isset($filters['status'])) {
            $query->where('notification_deliveries.status', $filters['status']);
        }

        if (isset($filters['channel'])) {
            $query->where('notification_deliveries.channel', $filters['channel']);
        }

        if (isset($filters['from'])) {
            $query->whereDate('notification_deliveries.created_at', '>=', $filters['from']);
        }

        if (isset($filters['to'])) {
            $query->whereDate('notification_deliveries.created_at', '<=', $filters['to']);
        }

        if (isset($filters['search'])) {
            $term = '%' . addcslashes($filters['search'], '%_\\\\') . '%';
            $query->where(function (Builder $nested) use ($term): void {
                $nested->where('notification_deliveries.notification_id', 'like', $term)
                    ->orWhere('notification_deliveries.notification_type', 'like', $term)
                    ->orWhere('notification_deliveries.last_error', 'like', $term)
                    ->orWhere('users.name', 'like', $term)
                    ->orWhere('users.email', 'like', $term);
            });
        }

        return $query;
    }
}
