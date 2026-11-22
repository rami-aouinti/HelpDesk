<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\ServiceDesk\Application\DTO\TicketSlaSnapshot;
use DateTimeImmutable;

interface TicketSlaRepository
{
    /**
     * Process active ticket identifiers in bounded batches.
     *
     * @param callable(list<int>): void $callback
     */
    public function eachActiveTicketId(callable $callback, int $chunkSize = 100): void;

    public function findForProcessing(int $ticketId): ?TicketSlaSnapshot;

    public function initializeDeadlines(
        int $ticketId,
        DateTimeImmutable $firstResponseDueAt,
        DateTimeImmutable $resolutionDueAt,
    ): void;

    public function recordFirstResponse(int $ticketId, DateTimeImmutable $respondedAt): void;

    /**
     * Atomically records an alert timestamp only if it has not been recorded yet.
     */
    public function markAlertSent(
        int $ticketId,
        string $slaType,
        string $state,
        DateTimeImmutable $sentAt,
    ): bool;
}
