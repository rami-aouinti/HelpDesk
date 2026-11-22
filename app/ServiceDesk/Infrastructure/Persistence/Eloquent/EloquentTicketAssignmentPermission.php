<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use App\ServiceDesk\Application\Contracts\TicketAssignmentPermission;

final class EloquentTicketAssignmentPermission implements TicketAssignmentPermission
{
    public function mayAssignTickets(int $actorId): bool
    {
        return User::query()->findOrFail($actorId)->hasRole('admin');
    }
}
