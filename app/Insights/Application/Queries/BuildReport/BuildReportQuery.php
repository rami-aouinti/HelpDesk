<?php

namespace App\Insights\Application\Queries\BuildReport;

final readonly class BuildReportQuery
{
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(public array $filters)
    {
    }
}
