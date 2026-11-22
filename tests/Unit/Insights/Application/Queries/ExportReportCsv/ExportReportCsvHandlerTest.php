<?php

declare(strict_types=1);

namespace Tests\Unit\Insights\Application\Queries\ExportReportCsv;

use App\Insights\Application\Contracts\ReportCsvExportRepository;
use App\Insights\Application\DTO\ReportCsvRow;
use App\Insights\Application\Queries\ExportReportCsv\ExportReportCsvHandler;
use App\Insights\Application\Queries\ExportReportCsv\ExportReportCsvQuery;
use PHPUnit\Framework\TestCase;

final class ExportReportCsvHandlerTest extends TestCase
{
    public function test_it_delegates_csv_row_retrieval_to_the_export_repository(): void
    {
        $filters = ['team_id' => 9, 'from' => '2026-11-01'];
        $rows = [new ReportCsvRow(1, 'VPN access', 'open', 'high', '2026-11-20 10:00:00', 'Customer', null, 'Support', null, 15)];

        $repository = $this->createMock(ReportCsvExportRepository::class);
        $repository->expects(self::once())
            ->method('rows')
            ->with($filters)
            ->willReturn($rows);

        self::assertSame($rows, (new ExportReportCsvHandler($repository))->handle(new ExportReportCsvQuery($filters)));
    }
}
