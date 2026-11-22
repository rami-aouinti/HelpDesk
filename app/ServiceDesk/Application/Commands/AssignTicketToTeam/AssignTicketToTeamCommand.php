<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AssignTicketToTeam;

final readonly class AssignTicketToTeamCommand
{
    public function __construct(
        public int $ticketId,
        public int $actorId,
        public ?int $teamId,
    ) {
    }
}
