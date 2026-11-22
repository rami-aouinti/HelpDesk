<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\UpdateTicket;

final readonly class UpdateTicketCommand
{
    /**
     * @param array<string, mixed> $changes
     */
    public function __construct(
        public int $ticketId,
        public int $actorId,
        public array $changes,
    ) {
    }
}
