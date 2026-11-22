<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ChangeTicketStatus;

use App\ServiceDesk\Domain\Enums\TicketStatus;

final readonly class ChangeTicketStatusCommand
{
    public function __construct(
        public int $ticketId,
        public int $actorId,
        public TicketStatus $status,
    ) {
    }
}
