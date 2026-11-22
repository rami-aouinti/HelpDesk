<?php

namespace App\Services;

use App\Data\Tickets\CreateTicketData;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Http\Middleware\CacheApiGetResponse;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

/**
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

    public function create(CreateTicketData $data): Ticket
    {
        $ticket = Ticket::query()->create([
            'subject' => $data->subject,
            'description' => $data->description,
            'priority' => $data->priority,
            'project_id' => $data->projectId,
            'status' => 'open',
            'user_id' => $data->customerId,
        ]);

        event(new TicketCreated($ticket));
        CacheApiGetResponse::flush();

        return $ticket;
    }

    /**
     * @throws Throwable
     */
    public function update(Ticket $ticket, array $data, ?int $userId = null): Ticket
    {
        $this->validateAgentTeamAccess($data, $ticket, $userId);
        $this->validateProjectMembership($data, $ticket, $userId);
        $this->validateAssignment($data, $ticket);
        $changes = [];

        $updatedTicket = DB::transaction(function () use ($ticket, $data, $userId, &$changes) {
            $ticket = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticket->id);

            $actor = $userId === null ? null : User::query()->findOrFail($userId);

            if (
                array_intersect(['team_id', 'assigned_to'], array_keys($data)) !== []
                && (! $actor || ! $actor->hasRole('admin'))
            ) {
                throw new AuthorizationException(
                    'Only administrators can assign or reassign tickets.'
                );
            }

            foreach ($data as $field => $newValue) {
                $allowedValues = match ($field) {
                    'status' => self::STATUSES,
                    'priority' => self::PRIORITIES,
                    'team_id', 'assigned_to', 'project_id', 'estimated_minutes', 'elapsed_minutes' => null,
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

                if ($field === 'status' && $oldValue !== $newValue && $actor !== null) {
                    $this->validateStatusTransition($oldValue, $newValue, $actor);
                }

                if ($oldValue === $newValue) {
                    continue;
                }

                $ticket->{$field} = $newValue;
                $ticket->save();
                $changes[$field] = ['old' => $oldValue, 'new' => $newValue];

                TicketHistory::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $userId,
                    'action' => $field,
                    'old_value' => $oldValue === null ? null : (string) $oldValue,
                    'new_value' => $newValue === null ? null : (string) $newValue,
                ]);
            }

            return $ticket->refresh()->load(['customer', 'assignedAgent', 'team']);
        });

        CacheApiGetResponse::flush();

        if ($userId !== null) {
            event(new TicketUpdated(
                $updatedTicket,
                User::query()->findOrFail($userId),
                $changes,
            ));
        }

        return $updatedTicket;
    }

    /**
     * Claim an unassigned ticket for the authenticated support agent.
     *
     * @throws Throwable
     */
    public function claim(Ticket $ticket, User $agent, ?int $teamId = null): Ticket
    {
        if (! $agent->hasRole('agent') || ! $agent->can('tickets.update')) {
            throw ValidationException::withMessages([
                'ticket' => 'Only support agents can claim tickets.',
            ]);
        }

        $changes = [];

        $claimedTicket = DB::transaction(function () use ($ticket, $agent, $teamId, &$changes): Ticket {
            $lockedTicket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);

            if ($lockedTicket->assigned_to !== null) {
                throw ValidationException::withMessages([
                    'ticket' => 'This ticket has already been assigned to another agent.',
                ]);
            }

            $targetTeamId = $lockedTicket->team_id ?? $teamId;

            if ($targetTeamId === null) {
                throw ValidationException::withMessages([
                    'team_id' => 'Choose one of your teams before claiming this ticket.',
                ]);
            }

            if ($lockedTicket->team_id !== null && $teamId !== null && (int) $lockedTicket->team_id !== $teamId) {
                throw ValidationException::withMessages([
                    'team_id' => 'A ticket already assigned to a team cannot be claimed by another team.',
                ]);
            }

            $team = Team::query()->find($targetTeamId);

            if (! $team || ! $agent->teams()->whereKey($targetTeamId)->exists()) {
                throw ValidationException::withMessages([
                    'team_id' => 'You can only claim tickets for one of your teams.',
                ]);
            }

            if ($lockedTicket->team_id === null) {
                $lockedTicket->team_id = $targetTeamId;
                $changes['team_id'] = ['old' => null, 'new' => $targetTeamId];

                TicketHistory::create([
                    'ticket_id' => $lockedTicket->id,
                    'user_id' => $agent->id,
                    'action' => 'team_id',
                    'old_value' => null,
                    'new_value' => (string) $targetTeamId,
                ]);
            }

            $lockedTicket->assigned_to = $agent->id;
            $changes['assigned_to'] = ['old' => null, 'new' => $agent->id];

            TicketHistory::create([
                'ticket_id' => $lockedTicket->id,
                'user_id' => $agent->id,
                'action' => 'assigned_to',
                'old_value' => null,
                'new_value' => (string) $agent->id,
            ]);

            if ($lockedTicket->status === 'open') {
                $lockedTicket->status = 'in_progress';
                $changes['status'] = ['old' => 'open', 'new' => 'in_progress'];

                TicketHistory::create([
                    'ticket_id' => $lockedTicket->id,
                    'user_id' => $agent->id,
                    'action' => 'status',
                    'old_value' => 'open',
                    'new_value' => 'in_progress',
                ]);
            }

            $lockedTicket->save();

            return $lockedTicket->refresh()->load(['customer', 'assignedAgent', 'team']);
        });

        event(new TicketUpdated($claimedTicket, $agent, $changes));
        CacheApiGetResponse::flush();

        return $claimedTicket;
    }

    /**
     * Enforce the ticket workflow for non-administrators.
     */
    private function validateStatusTransition(string $oldStatus, string $newStatus, User $actor): void
    {
        if ($actor->hasRole('admin')) {
            return;
        }

        $transitions = [
            'open' => ['in_progress'],
            'in_progress' => ['waiting_for_customer', 'resolved'],
            'waiting_for_customer' => ['in_progress'],
            'resolved' => ['closed'],
            'closed' => [],
        ];

        if (! in_array($newStatus, $transitions[$oldStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'This status transition is not allowed.',
            ]);
        }
    }

    /**
     * A non-admin can only link a ticket to a project they belong to.
     */
    private function validateProjectMembership(array $data, Ticket $ticket, ?int $userId): void
    {
        if ($userId === null) {
            return;
        }
        $user = User::query()->findOrFail($userId);
        if ($user->can('projects.view.all')) {
            return;
        }
        $projectId = array_key_exists('project_id', $data) ? $data['project_id'] : $ticket->project_id;
        if ($projectId !== null && ! $user->projects()->whereKey($projectId)->exists()) {
            throw ValidationException::withMessages(['project_id' => 'You must be a member of the selected project.']);
        }
    }

    /**
     * Agents may only assign tickets to one of their own teams.
     *
     * @param  array<string, mixed>  $data
     */
    private function validateAgentTeamAccess(array $data, Ticket $ticket, ?int $userId): void
    {
        if ($userId === null) {
            return;
        }

        $user = User::query()->findOrFail($userId);

        if (! $user->hasRole('agent') || $user->can('tickets.view.all')) {
            return;
        }

        $teamId = array_key_exists('team_id', $data) ? $data['team_id'] : $ticket->team_id;

        if ($teamId !== null && ! $user->teams()->whereKey($teamId)->exists()) {
            throw ValidationException::withMessages([
                'team_id' => 'You can only assign tickets to one of your teams.',
            ]);
        }
    }

    /**
     * Ensure an assigned agent is active and belongs to the selected team.
     *
     * @param  array<string, mixed>  $data
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
