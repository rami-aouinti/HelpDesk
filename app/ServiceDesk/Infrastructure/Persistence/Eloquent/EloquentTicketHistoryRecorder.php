<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\TicketHistory;
use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;

final class EloquentTicketHistoryRecorder implements TicketHistoryRecorder
{
    public function record(
        int $ticketId,
        int $actorId,
        string $action,
        ?string $oldValue,
        ?string $newValue,
    ): void {
        TicketHistory::query()->create([
            'ticket_id' => $ticketId,
            'user_id' => $actorId,
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
}
