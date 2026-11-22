<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Events;

final readonly class TicketWasCreated
{
    public function __construct(
        public int $ticketId,
        public int $customerId,
    ) {
    }
}
