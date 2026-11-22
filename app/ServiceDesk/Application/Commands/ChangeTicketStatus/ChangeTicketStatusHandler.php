<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ChangeTicketStatus;

use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TicketWorkflowPermission;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class ChangeTicketStatusHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketWorkflowPermission $workflowPermission,
        private readonly TicketHistoryRecorder $history,
        private readonly TransactionManager $transactions,
        private readonly TicketUpdatedPublisher $publisher,
    ) {
    }

    public function handle(ChangeTicketStatusCommand $command): void
    {
        $event = $this->transactions->run(function () use ($command): ?TicketWasUpdated {
            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $oldStatus = $ticket->status();

            if ($oldStatus === $command->status) {
                return null;
            }

            $ticket->changeStatus(
                $command->status,
                $this->workflowPermission->mayOverrideStatusWorkflow($command->actorId),
            );

            $this->tickets->save($ticket);
            $this->history->record(
                $command->ticketId,
                $command->actorId,
                'status',
                $oldStatus->value,
                $command->status->value,
            );

            return new TicketWasUpdated(
                $command->ticketId,
                $command->actorId,
                ['status' => ['old' => $oldStatus->value, 'new' => $command->status->value]],
            );
        });

        if ($event !== null) {
            $this->publisher->publish($event);
        }
    }
}
