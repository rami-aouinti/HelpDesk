<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ChangeTicketPriority;

use App\ServiceDesk\Domain\Enums\TicketPriority;

final readonly class ChangeTicketPriorityCommand
{
    public function __construct(
        public int $ticketId,
        public int $actorId,
        public TicketPriority $priority,
    ) {
    }
}
