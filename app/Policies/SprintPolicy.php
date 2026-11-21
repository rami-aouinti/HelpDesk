<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sprint;
use App\Models\User;

class SprintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('agent') && $user->can('tickets.view'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Sprint $sprint): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Sprint $sprint): bool
    {
        return $user->hasRole('admin');
    }
}
