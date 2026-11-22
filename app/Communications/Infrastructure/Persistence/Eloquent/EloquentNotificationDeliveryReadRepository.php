<?php

declare(strict_types=1);

namespace App\Communications\Infrastructure\Persistence\Eloquent;

use App\Communications\Application\Contracts\NotificationDeliveryReadRepository;
use Illuminate\Database\Query\Builder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class EloquentNotificationDeliveryReadRepository implements NotificationDeliveryReadRepository
{
    public function paginate(array $filters, int $perPage): mixed
    {
        $deliveries = $this->filteredQuery($filters)
            ->select('notification_deliveries.*')
            ->orderByDesc('notification_deliveries.created_at')
            ->paginate($perPage)
            ->withQueryString();

        // Resolve recipients separately: notifiable_id is a string by design.
        $userIds = $deliveries->getCollection()
            ->filter(fn (object $delivery): bool => in_array($delivery->notifiable_type, [User::class, 'users'], true))
            ->pluck('notifiable_id')
            ->map(fn (string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $users = $userIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

        $deliveries->getCollection()->transform(function (object $delivery) use ($users): object {
            $user = in_array($delivery->notifiable_type, [User::class, 'users'], true)
                ? $users->get((int) $delivery->notifiable_id)
                : null;

            $delivery->recipient_name = $user?->name;
            $delivery->recipient_email = $user?->email;

            return $delivery;
        });

        return $deliveries;
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
        $query = DB::table('notification_deliveries');

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
                    ->orWhereIn('notification_deliveries.notifiable_id', function ($users) use ($term): void {
                        $users->selectRaw('CAST(id AS CHAR)')
                            ->from('users')
                            ->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term);
                    });
            });
        }

        return $query;
    }
}
