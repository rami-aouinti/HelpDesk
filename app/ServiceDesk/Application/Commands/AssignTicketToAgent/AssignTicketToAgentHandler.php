<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AssignTicketToAgent;

use App\ServiceDesk\Application\Contracts\TicketAssignmentPermission;
use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketTeamMembership;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketData;
use App\ServiceDesk\Domain\Exceptions\TicketAssignmentRejected;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class AssignTicketToAgentHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketAssignmentPermission $assignmentPermission,
        private readonly TicketTeamMembership $teamMembership,
        private readonly TicketHistoryRecorder $history,
        private readonly TransactionManager $transactions,
        private readonly TicketUpdatedPublisher $publisher,
    ) {
    }

    public function handle(AssignTicketToAgentCommand $command): void
    {
        $event = $this->transactions->run(function () use ($command): ?TicketWasUpdated {
            if (! $this->assignmentPermission->mayAssignTickets($command->actorId)) {
                throw new TicketAssignmentRejected('assigned_to', 'Only administrators can assign or reassign tickets.');
            }

            if (! $this->teamMembership->agentBelongsToTeam($command->agentId, $command->teamId)) {
                throw new TicketAssignmentRejected(
                    'assigned_to',
                    'The selected agent must be an active member of the selected team.',
                );
            }

            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $oldTeamId = $ticket->teamId();
            $oldAgentId = $ticket->assignedAgentId();

            try {
                $ticket->assignToAgent($command->agentId, $command->teamId);
            } catch (InvalidTicketData $exception) {
                throw new TicketAssignmentRejected('assigned_to', $exception->getMessage());
            }

            if ($oldTeamId === $ticket->teamId() && $oldAgentId === $ticket->assignedAgentId()) {
                return null;
            }

            $this->tickets->save($ticket);
            $changes = [];

            if ($oldTeamId !== $ticket->teamId()) {
                $changes['team_id'] = ['old' => $oldTeamId, 'new' => $ticket->teamId()];
                $this->history->record(
                    $command->ticketId,
                    $command->actorId,
                    'team_id',
                    $oldTeamId === null ? null : (string) $oldTeamId,
                    (string) $ticket->teamId(),
                );
            }

            if ($oldAgentId !== $ticket->assignedAgentId()) {
                $changes['assigned_to'] = [
                    'old' => $oldAgentId,
                    'new' => $ticket->assignedAgentId(),
                ];
                $this->history->record(
                    $command->ticketId,
                    $command->actorId,
                    'assigned_to',
                    $oldAgentId === null ? null : (string) $oldAgentId,
                    (string) $ticket->assignedAgentId(),
                );
            }

            return new TicketWasUpdated($command->ticketId, $command->actorId, $changes);
        });

        if ($event !== null) {
            $this->publisher->publish($event);
        }
    }
}
