<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\Ticket as EloquentTicket;
use App\ServiceDesk\Domain\Entities\Ticket;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class EloquentTicketRepository implements TicketRepository
{
    public function save(Ticket $ticket): void
    {
        $record = EloquentTicket::query()->create([
            'subject' => $ticket->subject(),
            'description' => $ticket->description(),
            'priority' => $ticket->priority()->value,
            'project_id' => $ticket->projectId(),
            'status' => $ticket->status()->value,
            'user_id' => $ticket->customerId(),
        ]);

        $ticket->assignId((int) $record->getKey());
    }
}
