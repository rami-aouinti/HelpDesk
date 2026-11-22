<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\UpdateTicket;

use App\ServiceDesk\Application\Contracts\TicketAssignmentPermission;
use App\ServiceDesk\Application\Contracts\TicketHistoryRecorder;
use App\ServiceDesk\Application\Contracts\TicketProjectAccess;
use App\ServiceDesk\Application\Contracts\TicketTeamMembership;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Application\Contracts\TicketWorkflowPermission;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use App\ServiceDesk\Domain\Enums\TicketPriority;
use App\ServiceDesk\Domain\Enums\TicketStatus;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;
use App\ServiceDesk\Domain\Exceptions\TicketAssignmentRejected;
use App\ServiceDesk\Domain\Exceptions\TicketProjectLinkRejected;
use App\ServiceDesk\Domain\Repositories\TicketRepository;

final class UpdateTicketHandler
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly TicketAssignmentPermission $assignmentPermission,
        private readonly TicketTeamMembership $teamMembership,
        private readonly TicketProjectAccess $projectAccess,
        private readonly TicketWorkflowPermission $workflowPermission,
        private readonly TicketHistoryRecorder $history,
        private readonly TransactionManager $transactions,
        private readonly TicketUpdatedPublisher $publisher,
    ) {
    }

    public function handle(UpdateTicketCommand $command): void
    {
        if ($command->changes === []) {
            return;
        }

        $event = $this->transactions->run(function () use ($command): ?TicketWasUpdated {
            $ticket = $this->tickets->findForUpdate($command->ticketId);
            $changes = [];
            $history = [];
            $input = $command->changes;

            $assignmentRequested = array_key_exists('team_id', $input) || array_key_exists('assigned_to', $input);
            if ($assignmentRequested && ! $this->assignmentPermission->mayAssignTickets($command->actorId)) {
                throw new TicketAssignmentRejected('team_id', 'Only administrators can assign or reassign tickets.');
            }

            $targetProjectId = array_key_exists('project_id', $input)
                ? ($input['project_id'] === null ? null : (int) $input['project_id'])
                : $ticket->projectId();

            if (! $this->projectAccess->mayLinkToProject($command->actorId, $targetProjectId)) {
                throw new TicketProjectLinkRejected('project_id', 'You must be a member of the selected project.');
            }

            if (array_key_exists('project_id', $input)) {
                $old = $ticket->projectId();
                if ($old !== $targetProjectId) {
                    $ticket->linkToProject($targetProjectId);
                    $this->addChange($changes, $history, 'project_id', $old, $targetProjectId);
                }
            }

            if (array_key_exists('team_id', $input) || array_key_exists('assigned_to', $input)) {
                $teamId = array_key_exists('team_id', $input)
                    ? ($input['team_id'] === null ? null : (int) $input['team_id'])
                    : $ticket->teamId();
                $agentId = array_key_exists('assigned_to', $input)
                    ? ($input['assigned_to'] === null ? null : (int) $input['assigned_to'])
                    : $ticket->assignedAgentId();

                if ($agentId !== null) {
                    if ($teamId === null) {
                        throw new TicketAssignmentRejected('assigned_to', 'Select a team before assigning an agent.');
                    }
                    if (! $this->teamMembership->agentBelongsToTeam($agentId, $teamId)) {
                        throw new TicketAssignmentRejected('assigned_to', 'The selected agent must be an active member of the selected team.');
                    }
                }

                if ($teamId === null && $agentId !== null) {
                    throw new TicketAssignmentRejected('team_id', 'Unassign the current agent before removing the ticket team.');
                }

                $oldTeamId = $ticket->teamId();
                $oldAgentId = $ticket->assignedAgentId();

                if ($teamId !== $oldTeamId) {
                    $ticket->changeTeam($teamId);
                    $this->addChange($changes, $history, 'team_id', $oldTeamId, $teamId);
                }
                if ($agentId !== $oldAgentId) {
                    if ($agentId === null) {
                        $ticket->unassignAgent();
                    } else {
                        $ticket->assignToAgent($agentId, (int) $teamId);
                    }
                    $this->addChange($changes, $history, 'assigned_to', $oldAgentId, $agentId);
                }
            }

            if (array_key_exists('status', $input)) {
                $newStatus = TicketStatus::from($input['status']);
                $oldStatus = $ticket->status();
                if ($oldStatus !== $newStatus) {
                    $ticket->changeStatus($newStatus, $this->workflowPermission->mayOverrideStatusWorkflow($command->actorId));
                    $this->addChange($changes, $history, 'status', $oldStatus->value, $newStatus->value);
                }
            }

            if (array_key_exists('priority', $input)) {
                $newPriority = TicketPriority::from($input['priority']);
                $oldPriority = $ticket->priority();
                if ($oldPriority !== $newPriority) {
                    $ticket->changePriority($newPriority);
                    $this->addChange($changes, $history, 'priority', $oldPriority->value, $newPriority->value);
                }
            }

            if (array_key_exists('estimated_minutes', $input) || array_key_exists('elapsed_minutes', $input)) {
                $oldEstimated = $ticket->estimatedMinutes();
                $oldElapsed = $ticket->elapsedMinutes();
                $newEstimated = array_key_exists('estimated_minutes', $input)
                    ? ($input['estimated_minutes'] === null ? null : (int) $input['estimated_minutes'])
                    : $oldEstimated;
                $newElapsed = array_key_exists('elapsed_minutes', $input)
                    ? ($input['elapsed_minutes'] === null ? null : (int) $input['elapsed_minutes'])
                    : $oldElapsed;
                $ticket->updateEffort($newEstimated, $newElapsed);
                if ($oldEstimated !== $newEstimated) {
                    $this->addChange($changes, $history, 'estimated_minutes', $oldEstimated, $newEstimated);
                }
                if ($oldElapsed !== $newElapsed) {
                    $this->addChange($changes, $history, 'elapsed_minutes', $oldElapsed, $newElapsed);
                }
            }

            if ($changes === []) {
                return null;
            }

            $this->tickets->save($ticket);
            foreach ($history as [$field, $old, $new]) {
                $this->history->record(
                    $command->ticketId,
                    $command->actorId,
                    $field,
                    $old === null ? null : (string) $old,
                    $new === null ? null : (string) $new,
                );
            }

            return new TicketWasUpdated($command->ticketId, $command->actorId, $changes);
        });

        if ($event !== null) {
            $this->publisher->publish($event);
        }
    }

    /**
     * @param array<string, array{old: mixed, new: mixed}> $changes
     * @param array<int, array{0: string, 1: mixed, 2: mixed}> $history
     */
    private function addChange(array &$changes, array &$history, string $field, mixed $old, mixed $new): void
    {
        $changes[$field] = ['old' => $old, 'new' => $new];
        $history[] = [$field, $old, $new];
    }
}
