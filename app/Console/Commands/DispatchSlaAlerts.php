<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\ServiceDesk\Application\Commands\DispatchSlaAlerts\DispatchSlaAlertsHandler;
use Illuminate\Console\Command;

final class DispatchSlaAlerts extends Command
{
    protected $signature = 'helpdesk:dispatch-sla-alerts {--limit=100 : Maximum number of alerts to dispatch}';

    protected $description = 'Dispatch pending SLA alerts from the transactional outbox';

    public function handle(DispatchSlaAlertsHandler $handler): int
    {
        $dispatched = $handler->handle((int) $this->option('limit'));

        $this->info(sprintf('SLA outbox processing completed (%d alerts dispatched).', $dispatched));

        return self::SUCCESS;
    }
}
