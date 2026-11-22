<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\DispatchSlaAlerts;

use App\ServiceDesk\Application\Contracts\SlaAlertOutboxRepository;
use App\ServiceDesk\Application\Contracts\SlaAlertPublisher;

final class DispatchSlaAlertsHandler
{
    public function __construct(
        private readonly SlaAlertOutboxRepository $outbox,
        private readonly SlaAlertPublisher $publisher,
    ) {
    }

    public function handle(int $limit = 100): int
    {
        $dispatched = 0;

        foreach ($this->outbox->pending($limit) as $message) {
            try {
                $this->publisher->publish($message->alert);
                $this->outbox->markDispatched($message->id);
                $dispatched++;
            } catch (\Throwable $exception) {
                $this->outbox->releaseForRetry(
                    $message->id,
                    $exception->getMessage(),
                );
            }
        }

        return $dispatched;
    }
}
