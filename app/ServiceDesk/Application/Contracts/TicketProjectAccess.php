<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketProjectAccess
{
    public function mayLinkToProject(int $actorId, ?int $projectId): bool;
}
