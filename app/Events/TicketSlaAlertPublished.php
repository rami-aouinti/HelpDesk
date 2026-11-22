<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ticket;

class TicketSlaAlertPublished
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $slaType,
        public readonly string $state,
    ) {
    }
}
