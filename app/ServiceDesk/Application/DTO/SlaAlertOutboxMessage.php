<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\DTO;

final readonly class SlaAlertOutboxMessage
{
    public function __construct(
        public int $id,
        public SlaAlert $alert,
    ) {
        if ($this->id < 1) {
            throw new \InvalidArgumentException('A valid outbox message identifier is required.');
        }
    }
}
