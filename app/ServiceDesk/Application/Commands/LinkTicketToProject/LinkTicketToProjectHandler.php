<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\LinkTicketToProject;

use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketProjectAccess;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketData;
use App\ServiceDesk\Domain\Exceptions\TicketProjectLinkRejected;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class LinkTicketToProjectHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketProjectAccess $projectAccess,
        private readonly TicketHistoryRecorder $history,
        private readonly TransactionManager $transactions,
        private readonly TicketUpdatedPublisher $publisher,
    ) {
    }

    public function handle(LinkTicketToProjectCommand $command): void
    {
        $event = $this->transactions->run(function () use ($command): ?TicketWasUpdated {
            if (! $this->projectAccess->mayLinkToProject($command->actorId, $command->projectId)) {
                throw new TicketProjectLinkRejected(
                    'project_id',
                    'You must be a member of the selected project.',
                );
            }

            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $oldProjectId = $ticket->projectId();

            if ($oldProjectId === $command->projectId) {
                return null;
            }

            try {
                $ticket->linkToProject($command->projectId);
            } catch (InvalidTicketData $exception) {
                throw new TicketProjectLinkRejected('project_id', $exception->getMessage());
            }
            $this->tickets->save($ticket);

            $this->history->record(
                $command->ticketId,
                $command->actorId,
                'project_id',
                $oldProjectId === null ? null : (string) $oldProjectId,
                $command->projectId === null ? null : (string) $command->projectId,
            );

            return new TicketWasUpdated(
                $command->ticketId,
                $command->actorId,
                ['project_id' => ['old' => $oldProjectId, 'new' => $command->projectId]],
            );
        });

        if ($event !== null) {
            $this->publisher->publish($event);
        }
    }
}
