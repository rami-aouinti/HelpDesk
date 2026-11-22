<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\ServiceDesk\Application\DTO\SlaAlert;
use App\ServiceDesk\Application\DTO\TicketSlaSnapshot;
use DateTimeImmutable;

interface TicketSlaRepository
{
    /** @param callable(list<int>): void $callback */
    public function eachActiveTicketId(callable $callback, int $chunkSize = 100): void;

    public function findForProcessing(int $ticketId): ?TicketSlaSnapshot;

    public function initializeDeadlines(int $ticketId, DateTimeImmutable $firstResponseDueAt, DateTimeImmutable $resolutionDueAt): void;

    public function recordFirstResponse(int $ticketId, DateTimeImmutable $respondedAt): void;

    public function enqueueAlertIfNotRecorded(SlaAlert $alert, DateTimeImmutable $recordedAt): bool;
}
