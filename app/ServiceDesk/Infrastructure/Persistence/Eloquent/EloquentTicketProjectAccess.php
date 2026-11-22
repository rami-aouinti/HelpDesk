<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\User;
use App\ServiceDesk\Application\Contracts\TicketProjectAccess;

final class EloquentTicketProjectAccess implements TicketProjectAccess
{
    public function mayLinkToProject(int $actorId, ?int $projectId): bool
    {
        if ($projectId === null) {
            return true;
        }

        $actor = User::query()->findOrFail($actorId);

        return $actor->can('projects.view.all')
            || $actor->projects()->whereKey($projectId)->exists();
    }
}
