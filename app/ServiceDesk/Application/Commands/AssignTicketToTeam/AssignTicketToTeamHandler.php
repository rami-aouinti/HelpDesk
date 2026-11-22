<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AssignTicketToTeam;

use App\ServiceDesk\Application\Contracts\TicketAssignmentPermission;
use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketTeamMembership;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;
use App\ServiceDesk\Domain\Exceptions\TicketAssignmentRejected;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class AssignTicketToTeamHandler
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

    public function handle(AssignTicketToTeamCommand $command): void
    {
        $event = $this->transactions->run(function () use ($command): ?TicketWasUpdated {
            if (! $this->assignmentPermission->mayAssignTickets($command->actorId)) {
                throw new TicketAssignmentRejected('team_id', 'Only administrators can change a ticket team.');
            }

            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $oldTeamId = $ticket->teamId();

            if ($oldTeamId === $command->teamId) {
                return null;
            }

            if ($ticket->assignedAgentId() !== null) {
                if ($command->teamId === null) {
                    throw new TicketAssignmentRejected(
                        'team_id',
                        'Unassign the current agent before removing the ticket team.',
                    );
                }

                if (! $this->teamMembership->agentBelongsToTeam($ticket->assignedAgentId(), $command->teamId)) {
                    throw new TicketAssignmentRejected(
                        'team_id',
                        'The current assigned agent must belong to the selected team.',
                    );
                }
            }

            $ticket->changeTeam($command->teamId);
            $this->tickets->save($ticket);

            $this->history->record(
                $command->ticketId,
                $command->actorId,
                'team_id',
                $oldTeamId === null ? null : (string) $oldTeamId,
                $command->teamId === null ? null : (string) $command->teamId,
            );

            return new TicketWasUpdated(
                $command->ticketId,
                $command->actorId,
                ['team_id' => ['old' => $oldTeamId, 'new' => $command->teamId]],
            );
        });

        if ($event !== null) {
            $this->publisher->publish($event);
        }
    }
}
