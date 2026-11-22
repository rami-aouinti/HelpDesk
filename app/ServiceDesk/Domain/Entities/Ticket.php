<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Entities;

use App\ServiceDesk\Domain\Enums\TicketPriority;
use App\ServiceDesk\Domain\Enums\TicketStatus;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketData;

final class Ticket
{
    private function __construct(
        private ?int $id,
        private string $subject,
        private string $description,
        private TicketPriority $priority,
        private ?int $projectId,
        private int $customerId,
        private TicketStatus $status,
        private ?int $teamId = null,
        private ?int $assignedAgentId = null,
    ) {
        if (trim($this->subject) === '') {
            throw new InvalidTicketData('A ticket subject is required.');
        }

        if (trim($this->description) === '') {
            throw new InvalidTicketData('A ticket description is required.');
        }

        if ($this->customerId < 1) {
            throw new InvalidTicketData('A valid customer is required.');
        }

        if ($this->projectId !== null && $this->projectId < 1) {
            throw new InvalidTicketData('A project identifier must be positive.');
        }
    }

    public static function open(
        string $subject,
        string $description,
        TicketPriority $priority,
        ?int $projectId,
        int $customerId,
    ): self {
        return new self(
            id: null,
            subject: trim($subject),
            description: trim($description),
            priority: $priority,
            projectId: $projectId,
            customerId: $customerId,
            status: TicketStatus::OPEN,
            teamId: null,
            assignedAgentId: null,
        );
    }

    public static function reconstitute(
        int $id,
        string $subject,
        string $description,
        TicketPriority $priority,
        ?int $projectId,
        int $customerId,
        TicketStatus $status,
        ?int $teamId = null,
        ?int $assignedAgentId = null,
    ): self {
        return new self(
            $id,
            $subject,
            $description,
            $priority,
            $projectId,
            $customerId,
            $status,
            $teamId,
            $assignedAgentId,
        );
    }

    public function assignId(int $id): void
    {
        if ($id < 1 || $this->id !== null) {
            throw new InvalidTicketData('The ticket identifier is invalid or already assigned.');
        }

        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function priority(): TicketPriority
    {
        return $this->priority;
    }

    public function projectId(): ?int
    {
        return $this->projectId;
    }

    public function customerId(): int
    {
        return $this->customerId;
    }

    public function status(): TicketStatus
    {
        return $this->status;
    }

    public function teamId(): ?int
    {
        return $this->teamId;
    }

    public function assignedAgentId(): ?int
    {
        return $this->assignedAgentId;
    }

    /**
     * Claim this ticket for an agent belonging to the selected team.
     *
     * The caller must verify team membership; the aggregate protects the
     * invariants that are intrinsic to the ticket lifecycle.
     */
    /**
     * Change the ticket status according to the support workflow.
     *
     * Administrators may override the normal transition graph.
     */
    public function changeStatus(TicketStatus $newStatus, bool $mayOverrideWorkflow = false): void
    {
        if ($this->status === $newStatus) {
            return;
        }

        $allowedTransitions = [
            TicketStatus::OPEN->value => [TicketStatus::IN_PROGRESS],
            TicketStatus::IN_PROGRESS->value => [TicketStatus::WAITING_FOR_CUSTOMER, TicketStatus::RESOLVED],
            TicketStatus::WAITING_FOR_CUSTOMER->value => [TicketStatus::IN_PROGRESS],
            TicketStatus::RESOLVED->value => [TicketStatus::CLOSED],
            TicketStatus::CLOSED->value => [],
        ];

        if (! $mayOverrideWorkflow && ! in_array($newStatus, $allowedTransitions[$this->status->value], true)) {
            throw new \App\ServiceDesk\Domain\Exceptions\InvalidTicketTransition(
                sprintf('The transition from "%s" to "%s" is not allowed.', $this->status->value, $newStatus->value),
            );
        }

        $this->status = $newStatus;
    }

    public function claimBy(int $agentId, int $teamId): void
    {
        if ($agentId < 1 || $teamId < 1) {
            throw new InvalidTicketData('A valid agent and team are required to claim a ticket.');
        }

        if ($this->assignedAgentId !== null) {
            throw new InvalidTicketData('This ticket has already been assigned to another agent.');
        }

        if ($this->teamId !== null && $this->teamId !== $teamId) {
            throw new InvalidTicketData('A ticket already assigned to a team cannot be claimed by another team.');
        }

        $this->teamId ??= $teamId;
        $this->assignedAgentId = $agentId;

        if ($this->status === TicketStatus::OPEN) {
            $this->status = TicketStatus::IN_PROGRESS;
        }
    }
}
