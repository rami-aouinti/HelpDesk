<?php

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
