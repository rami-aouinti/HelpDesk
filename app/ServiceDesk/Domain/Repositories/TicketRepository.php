<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Repositories;

use App\ServiceDesk\Domain\Entities\Ticket;

interface TicketRepository
{
    /** Persist a new ticket and assign its generated identifier. */
    public function save(Ticket $ticket): void;
}
