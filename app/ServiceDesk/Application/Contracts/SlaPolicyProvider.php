<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\ServiceDesk\Domain\ValueObjects\SlaPolicy;

interface SlaPolicyProvider
{
    public function forPriority(string $priority): ?SlaPolicy;
}
