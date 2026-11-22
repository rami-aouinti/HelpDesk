<?php

declare(strict_types=1);

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
