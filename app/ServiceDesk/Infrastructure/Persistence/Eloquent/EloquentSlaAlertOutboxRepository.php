<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\ServiceDesk\Application\Contracts\SlaAlertOutboxRepository;
use App\ServiceDesk\Application\DTO\SlaAlert;
use App\ServiceDesk\Application\DTO\SlaAlertOutboxMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

final class EloquentSlaAlertOutboxRepository implements SlaAlertOutboxRepository
{
    public function pending(int $limit = 100): array
    {
        return DB::table('sla_alert_outbox')
            ->whereNull('dispatched_at')
            ->where('available_at', '<=', now())
            ->orderBy('id')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->map(static fn (object $row): SlaAlertOutboxMessage => new SlaAlertOutboxMessage(
                (int) $row->id,
                new SlaAlert(
                    (int) $row->ticket_id,
                    (string) $row->sla_type,
                    (string) $row->state,
                    Carbon::parse($row->due_at)->toDateTimeImmutable(),
                ),
            ))
            ->all();
    }

    public function markDispatched(int $id): void
    {
        DB::table('sla_alert_outbox')
            ->where('id', $id)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
                'last_error' => null,
            ]);
    }

    public function releaseForRetry(int $id, string $error): void
    {
        $row = DB::table('sla_alert_outbox')->where('id', $id)->first(['attempts']);

        if ($row === null) {
            return;
        }

        $nextAttempt = (int) $row->attempts + 1;
        $delayMinutes = min(60, 2 ** min($nextAttempt, 6));

        DB::table('sla_alert_outbox')
            ->where('id', $id)
            ->whereNull('dispatched_at')
            ->update([
                'attempts' => $nextAttempt,
                'available_at' => now()->addMinutes($delayMinutes),
                'last_error' => mb_substr($error, 0, 2000),
                'updated_at' => now(),
            ]);
    }
}
