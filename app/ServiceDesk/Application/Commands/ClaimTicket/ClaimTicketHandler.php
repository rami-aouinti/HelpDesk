<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ClaimTicket;

use App\ServiceDesk\Application\Contracts\TicketClaimedPublisher;
use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketTeamMembership;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Events\TicketWasClaimed;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketData;
use App\ServiceDesk\Domain\Exceptions\TicketClaimRejected;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class ClaimTicketHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketTeamMembership $teamMembership,
        private readonly TicketHistoryRecorder $history,
        private readonly TransactionManager $transactions,
        private readonly TicketClaimedPublisher $publisher,
    ) {
    }

    public function handle(ClaimTicketCommand $command): int
    {
        $event = $this->transactions->run(function () use ($command): TicketWasClaimed {
            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $teamId = $ticket->teamId() ?? $command->teamId;

            if ($ticket->assignedAgentId() !== null) {
                throw new TicketClaimRejected('ticket', 'This ticket has already been assigned to another agent.');
            }

            if ($ticket->teamId() !== null && $command->teamId !== null && $ticket->teamId() !== $command->teamId) {
                throw new TicketClaimRejected('team_id', 'A ticket already assigned to a team cannot be claimed by another team.');
            }

            if ($teamId === null) {
                throw new TicketClaimRejected('team_id', 'Choose one of your teams before claiming this ticket.');
            }

            if (! $this->teamMembership->agentBelongsToTeam($command->agentId, $teamId)) {
                throw new TicketClaimRejected('team_id', 'You can only claim tickets for one of your teams.');
            }

            $oldTeamId = $ticket->teamId();
            $oldStatus = $ticket->status();
            try {
                $ticket->claimBy($command->agentId, $teamId);
            } catch (InvalidTicketData $exception) {
                $field = str_contains(strtolower($exception->getMessage()), 'team') ? 'team_id' : 'ticket';
                throw new TicketClaimRejected($field, $exception->getMessage());
            }

            $this->tickets->save($ticket);
            $changes = [];

            if ($oldTeamId === null) {
                $changes['team_id'] = ['old' => null, 'new' => $teamId];
                $this->history->record($command->ticketId, $command->agentId, 'team_id', null, (string) $teamId);
            }

            $changes['assigned_to'] = ['old' => null, 'new' => $command->agentId];
            $this->history->record($command->ticketId, $command->agentId, 'assigned_to', null, (string) $command->agentId);

            if ($oldStatus !== $ticket->status()) {
                $changes['status'] = ['old' => $oldStatus->value, 'new' => $ticket->status()->value];
                $this->history->record(
                    $command->ticketId,
                    $command->agentId,
                    'status',
                    $oldStatus->value,
                    $ticket->status()->value,
                );
            }

            return new TicketWasClaimed($command->ticketId, $command->agentId, $changes);
        });

        $this->publisher->publish($event);

        return $event->ticketId;
    }
}
