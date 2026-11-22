<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketHistoryRecorder
{
    public function record(
        int $ticketId,
        int $actorId,
        string $action,
        ?string $oldValue,
        ?string $newValue,
    ): void;
}
