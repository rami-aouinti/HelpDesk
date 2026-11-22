<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ProcessTicketSla;

final readonly class ProcessTicketSlaCommand
{
    public function __construct(
        public int $ticketId,
    ) {
        if ($this->ticketId < 1) {
            throw new \InvalidArgumentException('A valid ticket identifier is required.');
        }
    }
}
