<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\ServiceDesk\Application\Commands\ProcessTicketSla\ProcessTicketSlaCommand;
use App\ServiceDesk\Application\Commands\ProcessTicketSla\ProcessTicketSlaHandler;
use Illuminate\Console\Command;

class ProcessTicketSlas extends Command
{
    protected $signature = 'helpdesk:process-slas';

    protected $description = 'Initialize ticket SLA deadlines and dispatch warning or breach alerts';

    public function handle(ProcessTicketSlaHandler $handler): int
    {
        $statuses = config('helpdesk.sla.active_statuses', []);

        Ticket::query()
            ->whereIn('status', $statuses)
            ->select('id')
            ->chunkById(100, function ($tickets) use ($handler): void {
                foreach ($tickets as $ticket) {
                    $handler->handle(new ProcessTicketSlaCommand((int) $ticket->getKey()));
                }
            });

        $this->info('Ticket SLA processing completed.');

        return self::SUCCESS;
    }
}
