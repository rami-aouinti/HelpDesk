<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceDesk\Application\DTO;

use App\ServiceDesk\Application\DTO\SlaAlert;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SlaAlertTest extends TestCase
{
    public function test_it_accepts_a_valid_sla_alert(): void
    {
        $alert = new SlaAlert(
            ticketId: 42,
            slaType: 'first_response',
            state: 'warning',
            dueAt: new DateTimeImmutable('2026-11-22 14:00:00'),
        );

        self::assertSame(42, $alert->ticketId);
        self::assertSame('first_response', $alert->slaType);
        self::assertSame('warning', $alert->state);
    }

    public function test_it_rejects_an_invalid_alert_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SlaAlert(42, 'assignment', 'warning', new DateTimeImmutable());
    }

    public function test_it_rejects_an_invalid_alert_state(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SlaAlert(42, 'resolution', 'ignored', new DateTimeImmutable());
    }
}
