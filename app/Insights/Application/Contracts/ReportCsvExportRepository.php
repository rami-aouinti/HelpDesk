<?php

namespace App\Insights\Application\Contracts;

use App\Insights\Application\DTO\ReportCsvRow;

interface ReportCsvExportRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return iterable<ReportCsvRow>
     */
    public function rows(array $filters): iterable;
}
