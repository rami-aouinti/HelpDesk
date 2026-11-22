<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class SlaPolicy
{
    /**
     * @param list<string> $resolvedStatuses
     */
    public function __construct(
        public int $firstResponseMinutes,
        public int $resolutionMinutes,
        public int $warningMinutes = 60,
        public array $resolvedStatuses = ['resolved', 'closed'],
    ) {
        if ($this->firstResponseMinutes < 1 || $this->resolutionMinutes < 1) {
            throw new InvalidArgumentException('SLA targets must be greater than zero minutes.');
        }

        if ($this->warningMinutes < 0) {
            throw new InvalidArgumentException('SLA warning minutes cannot be negative.');
        }
    }

    public function tracksResolutionFor(string $status): bool
    {
        return ! in_array($status, $this->resolvedStatuses, true);
    }
}
