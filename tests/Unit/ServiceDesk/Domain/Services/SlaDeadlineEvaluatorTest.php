<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceDesk\Domain\Services;

use App\ServiceDesk\Domain\Enums\SlaDeadlineAction;
use App\ServiceDesk\Domain\Services\SlaDeadlineEvaluator;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SlaDeadlineEvaluatorTest extends TestCase
{
    private SlaDeadlineEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->evaluator = new SlaDeadlineEvaluator();
    }

    public function test_it_returns_warning_when_the_warning_threshold_is_reached(): void
    {
        $action = $this->evaluator->evaluate(
            new DateTimeImmutable('2026-11-22 14:00:00'),
            new DateTimeImmutable('2026-11-22 13:00:00'),
            null,
            null,
            60,
        );

        self::assertSame(SlaDeadlineAction::WARNING, $action);
    }

    public function test_it_returns_breached_when_the_deadline_is_reached(): void
    {
        $action = $this->evaluator->evaluate(
            new DateTimeImmutable('2026-11-22 14:00:00'),
            new DateTimeImmutable('2026-11-22 14:00:00'),
            null,
            null,
            60,
        );

        self::assertSame(SlaDeadlineAction::BREACHED, $action);
    }

    public function test_it_does_not_repeat_an_already_sent_warning(): void
    {
        $action = $this->evaluator->evaluate(
            new DateTimeImmutable('2026-11-22 14:00:00'),
            new DateTimeImmutable('2026-11-22 13:30:00'),
            new DateTimeImmutable('2026-11-22 13:00:00'),
            null,
            60,
        );

        self::assertSame(SlaDeadlineAction::NONE, $action);
    }

    public function test_it_does_not_repeat_an_already_recorded_breach(): void
    {
        $action = $this->evaluator->evaluate(
            new DateTimeImmutable('2026-11-22 14:00:00'),
            new DateTimeImmutable('2026-11-22 15:00:00'),
            null,
            new DateTimeImmutable('2026-11-22 14:00:00'),
            60,
        );

        self::assertSame(SlaDeadlineAction::NONE, $action);
    }

    public function test_it_rejects_a_negative_warning_threshold(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->evaluator->evaluate(
            new DateTimeImmutable('2026-11-22 14:00:00'),
            new DateTimeImmutable('2026-11-22 13:00:00'),
            null,
            null,
            -1,
        );
    }
}
