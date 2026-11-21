<?php

namespace App\\Policies;

use App\\Models\\Ticket;
use App\\Models\\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('customer')) {
            return $ticket->user_id === $user->id;
        }

        return $user->hasAnyRole(['admin', 'agent']) && $user->can('tickets.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['admin', 'agent'])
            && $user->can('tickets.update');
    }
}
