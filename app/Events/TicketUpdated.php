<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;

/**
 * @param  array<string, array{old: mixed, new: mixed}>  $changes
 */
class TicketUpdated
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
        public readonly array $changes,
    ) {}
}
