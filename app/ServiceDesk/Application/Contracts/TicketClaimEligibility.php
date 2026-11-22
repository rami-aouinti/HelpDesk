<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketClaimEligibility
{
    public function canClaimForTeam(int $agentId, int $teamId): bool;
}
