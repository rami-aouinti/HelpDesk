<?php

namespace App\Insights\Application\Contracts;

interface ReportReadRepository
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function build(array $filters): array;
}
