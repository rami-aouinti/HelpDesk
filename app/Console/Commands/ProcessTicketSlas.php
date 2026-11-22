<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\ServiceDesk\Application\Commands\ProcessTicketSla\ProcessTicketSlaCommand;
use App\ServiceDesk\Application\Commands\ProcessTicketSla\ProcessTicketSlaHandler;
use App\ServiceDesk\Application\Contracts\TicketSlaRepository;
use Illuminate\Console\Command;

final class ProcessTicketSlas extends Command
{
    protected $signature = 'helpdesk:process-slas';

    protected $description = 'Initialize ticket SLA deadlines and dispatch warning or breach alerts';

    public function handle(
        TicketSlaRepository $tickets,
        ProcessTicketSlaHandler $handler,
    ): int {
        $processed = 0;

        $tickets->eachActiveTicketId(function (array $ticketIds) use ($handler, &$processed): void {
            foreach ($ticketIds as $ticketId) {
                $handler->handle(new ProcessTicketSlaCommand($ticketId));
                $processed++;
            }
        });

        $this->info(sprintf('Ticket SLA processing completed (%d tickets).', $processed));

        return self::SUCCESS;
    }
}
