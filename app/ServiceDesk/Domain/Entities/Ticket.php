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
    ): self {
        return new self($id, $subject, $description, $priority, $projectId, $customerId, $status);
    }

    public function assignId(int $id): void
    {
        if ($id < 1 || $this->id !== null) {
            throw new InvalidTicketData('The ticket identifier is invalid or already assigned.');
        }

        $this->id = $id;
    }

    public function id(): ?int { return $this->id; }
    public function subject(): string { return $this->subject; }
    public function description(): string { return $this->description; }
    public function priority(): TicketPriority { return $this->priority; }
    public function projectId(): ?int { return $this->projectId; }
    public function customerId(): int { return $this->customerId; }
    public function status(): TicketStatus { return $this->status; }
}
