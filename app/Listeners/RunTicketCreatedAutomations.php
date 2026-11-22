<?php

namespace App\Listeners;

use App\Automation\Application\AutomationRuleEngine;
use App\Events\TicketCreated;
use Illuminate\Contracts\Queue\ShouldQueue;

class RunTicketCreatedAutomations implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly AutomationRuleEngine $engine,
    ) {
    }

    public function handle(TicketCreated $event): void
    {
        $this->engine->dispatch('ticket.created', $event->ticket->fresh() ?? $event->ticket, [
            'source' => 'ticket.created',
        ]);
    }
}
