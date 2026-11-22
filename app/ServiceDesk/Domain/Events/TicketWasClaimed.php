<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Events;

final readonly class TicketWasClaimed
{
    /** @param array<string, array{old: mixed, new: mixed}> $changes */
    public function __construct(
        public int $ticketId,
        public int $agentId,
        public array $changes,
    ) {
    }
}
