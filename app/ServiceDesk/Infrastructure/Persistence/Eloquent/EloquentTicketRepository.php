<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\Ticket as EloquentTicket;
use App\ServiceDesk\Domain\Entities\Ticket;
use App\ServiceDesk\Domain\Enums\TicketPriority;
use App\ServiceDesk\Domain\Enums\TicketStatus;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class EloquentTicketRepository implements TicketRepository
{
    public function findForUpdate(int $ticketId): Ticket
    {
        $record = EloquentTicket::query()->lockForUpdate()->findOrFail($ticketId);

        return Ticket::reconstitute(
            id: (int) $record->id,
            subject: $record->subject,
            description: $record->description,
            priority: TicketPriority::from($record->priority),
            projectId: $record->project_id === null ? null : (int) $record->project_id,
            customerId: (int) $record->user_id,
            status: TicketStatus::from($record->status),
            teamId: $record->team_id === null ? null : (int) $record->team_id,
            assignedAgentId: $record->assigned_to === null ? null : (int) $record->assigned_to,
            estimatedMinutes: $record->estimated_minutes === null ? null : (int) $record->estimated_minutes,
            elapsedMinutes: $record->elapsed_minutes === null ? null : (int) $record->elapsed_minutes,
        );
    }

    public function save(Ticket $ticket): void
    {
        $attributes = [
            'subject' => $ticket->subject(),
            'description' => $ticket->description(),
            'priority' => $ticket->priority()->value,
            'project_id' => $ticket->projectId(),
            'status' => $ticket->status()->value,
            'user_id' => $ticket->customerId(),
            'team_id' => $ticket->teamId(),
            'assigned_to' => $ticket->assignedAgentId(),
            'estimated_minutes' => $ticket->estimatedMinutes(),
            'elapsed_minutes' => $ticket->elapsedMinutes(),
        ];

        if ($ticket->id() === null) {
            $record = EloquentTicket::query()->create($attributes);
            $ticket->assignId((int) $record->getKey());

            return;
        }

        EloquentTicket::query()->whereKey($ticket->id())->update($attributes);
    }
}
