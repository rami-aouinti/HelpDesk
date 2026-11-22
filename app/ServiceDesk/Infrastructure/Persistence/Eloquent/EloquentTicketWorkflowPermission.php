<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use App\ServiceDesk\Application\Contracts\TicketWorkflowPermission;

final class EloquentTicketWorkflowPermission implements TicketWorkflowPermission
{
    public function mayOverrideStatusWorkflow(int $actorId): bool
    {
        return User::query()->findOrFail($actorId)->hasRole('admin');
    }
}
