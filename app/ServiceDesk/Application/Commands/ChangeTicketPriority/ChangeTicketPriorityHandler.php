<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ChangeTicketPriority;

use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class ChangeTicketPriorityHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketHistoryRecorder $history,
        private readonly TransactionManager $transactions,
        private readonly TicketUpdatedPublisher $publisher,
    ) {
    }

    public function handle(ChangeTicketPriorityCommand $command): void
    {
        $event = $this->transactions->run(function () use ($command): ?TicketWasUpdated {
            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $oldPriority = $ticket->priority();

            if ($oldPriority === $command->priority) {
                return null;
            }

            $ticket->changePriority($command->priority);
            $this->tickets->save($ticket);

            $this->history->record(
                $command->ticketId,
                $command->actorId,
                'priority',
                $oldPriority->value,
                $command->priority->value,
            );

            return new TicketWasUpdated(
                $command->ticketId,
                $command->actorId,
                ['priority' => ['old' => $oldPriority->value, 'new' => $command->priority->value]],
            );
        });

        if ($event !== null) {
            $this->publisher->publish($event);
        }
    }
}
