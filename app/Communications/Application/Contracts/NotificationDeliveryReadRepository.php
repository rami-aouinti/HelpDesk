<?php

declare(strict_types=1);

namespace App\Communications\Application\Contracts;

interface NotificationDeliveryReadRepository
{
    /** @param array{status?: string, channel?: string, search?: string, from?: string, to?: string} $filters */
    public function paginate(array $filters, int $perPage): mixed;

    /** @param array{status?: string, channel?: string, search?: string, from?: string, to?: string} $filters
     *  @return array{total: int, pending: int, sent: int, failed: int}
     */
    public function summary(array $filters): array;
}
