<?php

namespace App\Listeners;

use App\Automation\Application\AutomationRuleEngine;
use App\Events\TicketUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;

class RunTicketUpdatedAutomations implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly AutomationRuleEngine $engine,
    ) {
    }

    public function handle(TicketUpdated $event): void
    {
        if (array_key_exists('priority', $event->changes)) {
            $this->engine->dispatch('ticket.priority_changed', $event->ticket->fresh() ?? $event->ticket, [
                'old_priority' => $event->changes['priority']['old'] ?? null,
                'new_priority' => $event->changes['priority']['new'] ?? null,
            ]);
        }
    }
}
