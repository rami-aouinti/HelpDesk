<?php

declare(strict_types=1);

namespace Tests\Unit\Insights\Application\DTO;

use App\Insights\Application\DTO\ReportCsvRow;
use PHPUnit\Framework\TestCase;

final class ReportCsvRowTest extends TestCase
{
    public function test_it_converts_a_report_row_to_csv_columns_in_the_expected_order(): void
    {
        $row = new ReportCsvRow(
            15,
            'Cannot access account',
            'open',
            'urgent',
            '2026-11-22 09:30:00',
            'Sam Customer',
            null,
            'Support',
            'Portal migration',
            42,
        );

        self::assertSame([
            15,
            'Cannot access account',
            'open',
            'urgent',
            '2026-11-22 09:30:00',
            'Sam Customer',
            '',
            'Support',
            'Portal migration',
            42,
        ], $row->toCsv());
    }
}
