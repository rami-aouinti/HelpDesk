<?php

declare(strict_types=1);

namespace Tests\Unit\Communications\Application\Queries;

use App\Communications\Application\Contracts\NotificationDeliveryReadRepository;
use App\Communications\Application\Queries\GetNotificationDeliveryOverview\GetNotificationDeliveryOverviewHandler;
use App\Communications\Application\Queries\GetNotificationDeliveryOverview\GetNotificationDeliveryOverviewQuery;
use PHPUnit\Framework\TestCase;

final class NotificationDeliveryOverviewHandlerTest extends TestCase
{
    public function test_it_passes_filters_and_clamps_page_size(): void
    {
        $filters = ['status' => 'failed', 'channel' => 'mail', 'search' => '', 'from' => null];
        $summary = ['total' => 3, 'pending' => 0, 'sent' => 0, 'failed' => 3];
        $repository = $this->createMock(NotificationDeliveryReadRepository::class);

        $repository->expects(self::once())
            ->method('paginate')
            ->with(['status' => 'failed', 'channel' => 'mail'], 100)
            ->willReturn('paginator');
        $repository->expects(self::once())
            ->method('summary')
            ->with(['status' => 'failed', 'channel' => 'mail'])
            ->willReturn($summary);

        $result = (new GetNotificationDeliveryOverviewHandler($repository))->handle(
            new GetNotificationDeliveryOverviewQuery($filters, 500),
        );

        self::assertSame('paginator', $result['deliveries']);
        self::assertSame($summary, $result['summary']);
    }
}
