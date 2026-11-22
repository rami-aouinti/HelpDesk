<?php

namespace App\Insights\Application\Queries\ExportReportCsv;

final readonly class ExportReportCsvQuery
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(public array $filters)
    {
    }
}
