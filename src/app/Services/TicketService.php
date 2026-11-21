<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * @package App\Services
 * @author Rami Aouinti
 */
class TicketService
{
    private const array STATUSES = [
        'open',
        'in_progress',
        'waiting_for_customer',
        'resolved',
        'closed',
    ];

    private const array PRIORITIES = [
        'low',
        'normal',
        'high',
        'urgent',
    ];

    /**
     * @throws Throwable
     */
    public function update(Ticket $ticket, array $data, ?int $userId = null): Ticket
    {
        $this->validateAssignment($data, $ticket);

        return DB::transaction(function () use ($ticket, $data, $userId) {
            $ticket = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticket->id);

            foreach ($data as $field => $newValue) {
                $allowedValues = match ($field) {
                    'status' => self::STATUSES,
                    'priority' => self::PRIORITIES,
                    'team_id', 'assigned_to' => null,
                    default => throw new InvalidArgumentException(
                        "Unsupported ticket field: {$field}"
                    ),
                };

                if ($allowedValues !== null && ! in_array($newValue, $allowedValues, true)) {
                    throw new InvalidArgumentException(
                        "Invalid value for {$field}."
                    );
                }

                $oldValue = $ticket->{$field};

                if ($oldValue === $newValue) {
                    continue;
                }

                $ticket->{$field} = $newValue;
                $ticket->save();

                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $userId,
                    'action' => $field,
                    'old_value' => $oldValue === null ? null : (string) $oldValue,
                    'new_value' => $newValue === null ? null : (string) $newValue,
                ]);
            }

            return $ticket->refresh();
        });
    }

    /**
     * Ensure an assigned agent is active and belongs to the selected team.
     *
     * @param array<string, mixed> $data
     */
    private function validateAssignment(array $data, Ticket $ticket): void
    {
        if (! array_key_exists('team_id', $data) && ! array_key_exists('assigned_to', $data)) {
            return;
        }

        $teamId = array_key_exists('team_id', $data) ? $data['team_id'] : $ticket->team_id;
        $agentId = array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $ticket->assigned_to;

        if ($agentId === null || $agentId === '') {
            return;
        }

        if ($teamId === null || $teamId === '') {
            throw ValidationException::withMessages([
                'assigned_to' => 'Select a team before assigning an agent.',
            ]);
        }

        $team = Team::query()->find($teamId);
        $agent = User::query()
            ->whereKey($agentId)
            ->where('is_active', true)
            ->role('agent')
            ->first();

        if (! $team || ! $agent || ! $team->members()->whereKey($agent->id)->exists()) {
            throw ValidationException::withMessages([
                'assigned_to' => 'The selected agent must be an active member of the selected team.',
            ]);
        }
    }
}
