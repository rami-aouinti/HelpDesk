<?php

namespace App\Listeners;

use App\Automation\Application\AutomationRuleEngine;
use App\Events\TicketSlaAlertPublished;
use Illuminate\Contracts\Queue\ShouldQueue;

class RunTicketSlaAutomations implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly AutomationRuleEngine $engine,
    ) {
    }

    public function handle(TicketSlaAlertPublished $event): void
    {
        if ($event->state === 'breached') {
            $this->engine->dispatch('ticket.sla_breached', $event->ticket->fresh() ?? $event->ticket, [
                'sla_type' => $event->slaType,
                'state' => $event->state,
            ]);
        }
    }
}
