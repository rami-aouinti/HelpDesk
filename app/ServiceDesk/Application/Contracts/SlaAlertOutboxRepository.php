<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\ServiceDesk\Application\DTO\SlaAlertOutboxMessage;

interface SlaAlertOutboxRepository
{
    /** @return list<SlaAlertOutboxMessage> */
    public function pending(int $limit = 100): array;

    public function markDispatched(int $id): void;

    public function releaseForRetry(int $id, string $error, int $attempts): void;
}
