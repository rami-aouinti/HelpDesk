<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ClaimTicket;

final readonly class ClaimTicketCommand
{
    public function __construct(
        public int $ticketId,
        public int $agentId,
        public ?int $teamId = null,
    ) {
    }
}
