<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\UnassignTicketFromAgent;

final readonly class UnassignTicketFromAgentCommand
{
    public function __construct(
        public int $ticketId,
        public int $actorId,
    ) {
    }
}
