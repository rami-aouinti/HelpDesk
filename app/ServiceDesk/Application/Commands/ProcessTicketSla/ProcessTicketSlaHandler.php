<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ProcessTicketSla;

use App\ServiceDesk\Application\Contracts\TicketSlaProcessor;

final class ProcessTicketSlaHandler
{
    public function __construct(
        private readonly TicketSlaProcessor $processor,
    ) {
    }

    public function handle(ProcessTicketSlaCommand $command): void
    {
        $this->processor->process($command->ticketId);
    }
}
