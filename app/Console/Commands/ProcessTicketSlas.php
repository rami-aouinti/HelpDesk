<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\TicketSlaService;
use Illuminate\Console\Command;

class ProcessTicketSlas extends Command
{
    protected $signature = 'helpdesk:process-slas';

    protected $description = 'Initialize ticket SLA deadlines and dispatch warning or breach alerts';

    public function handle(TicketSlaService $sla): int
    {
        $statuses = config('helpdesk.sla.active_statuses', []);

        Ticket::query()
            ->whereIn('status', $statuses)
            ->with(['assignedAgent'])
            ->chunkById(100, function ($tickets) use ($sla): void {
                foreach ($tickets as $ticket) {
                    $sla->process($ticket);
                }
            });

        $this->info('Ticket SLA processing completed.');

        return self::SUCCESS;
    }
}
