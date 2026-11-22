<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\DTO;

use DateTimeImmutable;

final readonly class SlaAlert
{
    public function __construct(
        public int $ticketId,
        public string $slaType,
        public string $state,
        public DateTimeImmutable $dueAt,
    ) {
        if ($this->ticketId < 1) {
            throw new \InvalidArgumentException('A valid ticket identifier is required.');
        }

        if (! in_array($this->slaType, ['first_response', 'resolution'], true)) {
            throw new \InvalidArgumentException('Unsupported SLA alert type.');
        }

        if (! in_array($this->state, ['warning', 'breached'], true)) {
            throw new \InvalidArgumentException('Unsupported SLA alert state.');
        }
    }
}
