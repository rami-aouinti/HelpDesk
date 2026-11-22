<?php

declare(strict_types=1);

namespace App\Insights\Application\Queries\BuildReport;

use App\Insights\Application\Contracts\ReportReadRepository;

final readonly class BuildReportHandler
{
    public function __construct(private ReportReadRepository $reports)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(BuildReportQuery $query): array
    {
        return $this->reports->build($query->filters);
    }
}
