<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ticket;

class TicketCreated
{
    public function __construct(public readonly Ticket $ticket) {}
}
