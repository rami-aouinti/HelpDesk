<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketTeamMembership
{
    public function agentBelongsToTeam(int $agentId, int $teamId): bool;
}
