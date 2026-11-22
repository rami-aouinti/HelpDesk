<?php

declare(strict_types=1);

namespace App\Insights\Application\Queries\ExportReportCsv;

use App\Insights\Application\Contracts\ReportCsvExportRepository;
use App\Insights\Application\DTO\ReportCsvRow;

final readonly class ExportReportCsvHandler
{
    public function __construct(private ReportCsvExportRepository $repository)
    {
    }

    /**
     * @return iterable<ReportCsvRow>
     */
    public function handle(ExportReportCsvQuery $query): iterable
    {
        return $this->repository->rows($query->filters);
    }
}
