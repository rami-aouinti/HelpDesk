<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\UpdateTicketEffort;

final readonly class UpdateTicketEffortCommand
{
    public function __construct(
        public int $ticketId,
        public int $actorId,
        public ?int $estimatedMinutes = null,
        public ?int $elapsedMinutes = null,
        public bool $updateEstimatedMinutes = false,
        public bool $updateElapsedMinutes = false,
    ) {
    }
}
