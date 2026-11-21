<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->can('tickets.view.all')) {
            return true;
        }

        if (! $user->can('tickets.view')) {
            return false;
        }

        if ($ticket->project_id !== null && ! $ticket->project->members()->whereKey($user->id)->exists()) {
            return false;
        }

        if ($user->hasRole('customer')) {
            return $ticket->user_id === $user->id;
        }

        return $user->hasRole('agent')
            && ($ticket->team_id === null || $user->teams()->whereKey($ticket->team_id)->exists())
            && ($ticket->project_id === null || $ticket->project->members()->whereKey($user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if (! $user->can('tickets.update')) {
            return false;
        }

        if ($user->can('tickets.view.all')) {
            return true;
        }

        return $user->hasRole('agent')
            && ($ticket->team_id === null || $user->teams()->whereKey($ticket->team_id)->exists())
            && ($ticket->project_id === null || $ticket->project->members()->whereKey($user->id)->exists());
    }
}
