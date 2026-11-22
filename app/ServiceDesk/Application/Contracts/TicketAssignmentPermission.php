<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketAssignmentPermission
{
    public function mayAssignTickets(int $actorId): bool;
}
