<?php

declare(strict_types=1);

namespace App\Communications\Application\Queries\GetNotificationDeliveryOverview;

use App\Communications\Application\Contracts\NotificationDeliveryReadRepository;

final readonly class GetNotificationDeliveryOverviewHandler
{
    public function __construct(private NotificationDeliveryReadRepository $deliveries)
    {
    }

    /** @return array{deliveries: mixed, summary: array{total: int, pending: int, sent: int, failed: int}} */
    public function handle(GetNotificationDeliveryOverviewQuery $query): array
    {
        $filters = array_filter(
            $query->filters,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
        $perPage = max(1, min($query->perPage, 100));

        return [
            'deliveries' => $this->deliveries->paginate($filters, $perPage),
            'summary' => $this->deliveries->summary($filters),
        ];
    }
}
