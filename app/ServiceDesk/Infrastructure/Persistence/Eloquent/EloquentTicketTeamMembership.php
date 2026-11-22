<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use App\ServiceDesk\Application\Contracts\TicketTeamMembership;

final class EloquentTicketTeamMembership implements TicketTeamMembership
{
    public function agentBelongsToTeam(int $agentId, int $teamId): bool
    {
        return User::query()
            ->whereKey($agentId)
            ->where('is_active', true)
            ->role('agent')
            ->whereHas('teams', fn ($query) => $query->whereKey($teamId))
            ->exists();
    }
}
