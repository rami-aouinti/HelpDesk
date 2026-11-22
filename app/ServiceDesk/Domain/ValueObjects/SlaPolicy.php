<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class SlaPolicy
{
    public function __construct(
        public int $firstResponseMinutes,
        public int $resolutionMinutes,
    ) {
        if ($this->firstResponseMinutes < 1 || $this->resolutionMinutes < 1) {
            throw new InvalidArgumentException('SLA targets must be greater than zero minutes.');
        }
    }
}
