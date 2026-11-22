<?php

declare(strict_types=1);

namespace Tests\Unit\Insights\Application\Queries\BuildReport;

use App\Insights\Application\Contracts\ReportReadRepository;
use App\Insights\Application\Queries\BuildReport\BuildReportHandler;
use App\Insights\Application\Queries\BuildReport\BuildReportQuery;
use PHPUnit\Framework\TestCase;

final class BuildReportHandlerTest extends TestCase
{
    public function test_it_delegates_report_generation_to_the_read_repository(): void
    {
        $filters = [
            'report' => 'tickets',
            'from' => '2026-11-01',
            'team_id' => 12,
        ];
        $expected = [
            'total' => 4,
            'resolved' => 2,
            'open' => 2,
            'urgent' => 1,
            'resolutionRate' => 50.0,
        ];

        $repository = $this->createMock(ReportReadRepository::class);
        $repository->expects(self::once())
            ->method('build')
            ->with($filters)
            ->willReturn($expected);

        self::assertSame($expected, (new BuildReportHandler($repository))->handle(new BuildReportQuery($filters)));
    }
}
