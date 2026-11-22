<?php

namespace App\Listeners;

use App\Automation\Application\AutomationRuleEngine;
use App\Events\TicketSlaAlertPublished;
use Illuminate\Contracts\Queue\ShouldQueue;

class RunTicketSlaAutomations implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(TicketSlaAlertPublished $event, AutomationRuleEngine $engine): void
    {
        if ($event->state === 'breached') {
            $engine->dispatch('ticket.sla_breached', $event->ticket->fresh() ?? $event->ticket, [
                'sla_type' => $event->slaType,
                'state' => $event->state,
            ]);
        }
    }
}
