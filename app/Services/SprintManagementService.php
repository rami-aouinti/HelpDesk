<?php

namespace App\Services;

use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SprintManagementService
{
    public function create(array $data, User $actor): Sprint
    {
        return Sprint::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'created_by' => $actor->id,
        ]);
    }

    public function update(Sprint $sprint, array $data): Sprint
    {
        return DB::transaction(function () use ($sprint, $data): Sprint {
            $lockedSprint = Sprint::query()->lockForUpdate()->findOrFail($sprint->id);

            $lockedSprint->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
            ]);

            return $lockedSprint->refresh();
        });
    }

    public function delete(Sprint $sprint): void
    {
        DB::transaction(function () use ($sprint): void {
            $lockedSprint = Sprint::query()->lockForUpdate()->findOrFail($sprint->id);

            if ($lockedSprint->tickets()->exists()) {
                throw ValidationException::withMessages([
                    'sprint' => 'Move all tickets out of this sprint before deleting it.',
                ]);
            }

            $lockedSprint->delete();
        });
    }

    public function moveTicket(Ticket $ticket, ?Sprint $destination, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $destination, $actor): Ticket {
            $lockedTicket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if (! $actor->hasRole('admin') && (int) $lockedTicket->assigned_to !== (int) $actor->id) {
                abort(403, 'You can only move tickets assigned to you.');
            }

            if ($destination instanceof Sprint) {
                $destination = Sprint::query()->lockForUpdate()->findOrFail($destination->id);
            }

            if ($destination?->is_completed) {
                throw ValidationException::withMessages([
                    'sprint' => 'Tickets cannot be added to a completed sprint.',
                ]);
            }

            $newSprintId = $destination?->id;

            if ((int) $lockedTicket->sprint_id === (int) $newSprintId) {
                return $lockedTicket->refresh();
            }

            $oldSprintId = $lockedTicket->sprint_id;
            $lockedTicket->sprint_id = $newSprintId;
            $lockedTicket->save();

            TicketHistory::create([
                'ticket_id' => $lockedTicket->id,
                'user_id' => $actor->id,
                'action' => 'sprint_id',
                'old_value' => $oldSprintId === null ? null : (string) $oldSprintId,
                'new_value' => $newSprintId === null ? null : (string) $newSprintId,
            ]);

            return $lockedTicket->refresh()->load(['customer', 'assignedAgent', 'team', 'sprint']);
        });
    }
}
