<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\UnassignTicketFromAgent;

use App\ServiceDesk\Application\Contracts\TicketAssignmentPermission;
use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;
use App\ServiceDesk\Domain\Exceptions\TicketAssignmentRejected;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class UnassignTicketFromAgentHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketAssignmentPermission $assignmentPermission,
        private readonly TicketHistoryRecorder $history,
        private readonly TransactionManager $transactions,
        private readonly TicketUpdatedPublisher $publisher,
    ) {
    }

    public function handle(UnassignTicketFromAgentCommand $command): void
    {
        $event = $this->transactions->run(function () use ($command): ?TicketWasUpdated {
            if (! $this->assignmentPermission->mayAssignTickets($command->actorId)) {
                throw new TicketAssignmentRejected('assigned_to', 'Only administrators can unassign tickets.');
            }

            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $oldAgentId = $ticket->assignedAgentId();

            if ($oldAgentId === null) {
                return null;
            }

            $ticket->unassignAgent();
            $this->tickets->save($ticket);

            $this->history->record(
                $command->ticketId,
                $command->actorId,
                'assigned_to',
                (string) $oldAgentId,
                null,
            );

            return new TicketWasUpdated(
                $command->ticketId,
                $command->actorId,
                ['assigned_to' => ['old' => $oldAgentId, 'new' => null]],
            );
        });

        if ($event !== null) {
            $this->publisher->publish($event);
        }
    }
}
