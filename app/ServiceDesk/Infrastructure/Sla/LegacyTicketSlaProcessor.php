<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Sla;

use App\Services\TicketSlaService;
use App\ServiceDesk\Application\Contracts\TicketSlaProcessor;

final class LegacyTicketSlaProcessor implements TicketSlaProcessor
{
    public function __construct(
        private readonly TicketSlaService $sla,
    ) {
    }

    public function process(int $ticketId): void
    {
        $this->sla->process($ticketId);
    }
}
