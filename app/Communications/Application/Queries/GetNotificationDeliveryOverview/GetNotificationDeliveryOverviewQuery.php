<?php

declare(strict_types=1);

namespace App\Communications\Application\Queries\GetNotificationDeliveryOverview;

final readonly class GetNotificationDeliveryOverviewQuery
{
    /** @param array{status?: string, channel?: string, search?: string, from?: string, to?: string} $filters */
    public function __construct(
        public array $filters = [],
        public int $perPage = 20,
    ) {
    }
}
