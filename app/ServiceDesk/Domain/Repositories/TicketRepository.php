<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Repositories;

use App\ServiceDesk\Domain\Entities\Ticket;

interface TicketRepository
{
    /** Load an aggregate while holding a database write lock. */
    public function findForUpdate(int $ticketId): Ticket;

    /** Persist a new or existing aggregate. */
    public function save(Ticket $ticket): void;
}
