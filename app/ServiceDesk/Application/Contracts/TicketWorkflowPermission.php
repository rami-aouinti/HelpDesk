<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketWorkflowPermission
{
    public function mayOverrideStatusWorkflow(int $actorId): bool;
}
