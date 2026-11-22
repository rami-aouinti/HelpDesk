<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Sla;

use App\ServiceDesk\Application\Contracts\SlaPolicyProvider;
use App\ServiceDesk\Domain\ValueObjects\SlaPolicy;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class ConfigSlaPolicyProvider implements SlaPolicyProvider
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    public function forPriority(string $priority): ?SlaPolicy
    {
        $targets = $this->config->get('helpdesk.sla.priorities.' . $priority);

        if (! is_array($targets)
            || ! isset($targets['first_response'], $targets['resolution'])) {
            return null;
        }

        return new SlaPolicy(
            firstResponseMinutes: (int) $targets['first_response'],
            resolutionMinutes: (int) $targets['resolution'],
            warningMinutes: (int) $this->config->get('helpdesk.sla.warning_minutes', 60),
            resolvedStatuses: (array) $this->config->get('helpdesk.sla.resolved_statuses', ['resolved', 'closed']),
        );
    }
}
