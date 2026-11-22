<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\UpdateTicketEffort;

use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class UpdateTicketEffortHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketHistoryRecorder $history,
        private readonly TransactionManager $transactions,
        private readonly TicketUpdatedPublisher $publisher,
    ) {
    }

    public function handle(UpdateTicketEffortCommand $command): void
    {
        if (! $command->updateEstimatedMinutes && ! $command->updateElapsedMinutes) {
            return;
        }

        $event = $this->transactions->run(function () use ($command): ?TicketWasUpdated {
            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $oldEstimated = $ticket->estimatedMinutes();
            $oldElapsed = $ticket->elapsedMinutes();

            $newEstimated = $command->updateEstimatedMinutes
                ? $command->estimatedMinutes
                : $oldEstimated;
            $newElapsed = $command->updateElapsedMinutes
                ? $command->elapsedMinutes
                : $oldElapsed;

            $changes = [];

            if ($oldEstimated !== $newEstimated) {
                $changes['estimated_minutes'] = ['old' => $oldEstimated, 'new' => $newEstimated];
            }

            if ($oldElapsed !== $newElapsed) {
                $changes['elapsed_minutes'] = ['old' => $oldElapsed, 'new' => $newElapsed];
            }

            if ($changes === []) {
                return null;
            }

            $ticket->updateEffort($newEstimated, $newElapsed);
            $this->tickets->save($ticket);

            foreach ($changes as $field => $change) {
                $this->history->record(
                    $command->ticketId,
                    $command->actorId,
                    $field,
                    $change['old'] === null ? null : (string) $change['old'],
                    $change['new'] === null ? null : (string) $change['new'],
                );
            }

            return new TicketWasUpdated($command->ticketId, $command->actorId, $changes);
        });

        if ($event !== null) {
            $this->publisher->publish($event);
        }
    }
}
