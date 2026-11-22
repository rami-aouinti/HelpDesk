<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AssignTicketToAgent;

final readonly class AssignTicketToAgentCommand
{
    public function __construct(
        public int $ticketId,
        public int $actorId,
        public int $agentId,
        public int $teamId,
    ) {
    }
}
