<?php

declare(strict_types=1);

namespace App\ServiceDesk\Domain\Services;

use App\ServiceDesk\Domain\Enums\SlaDeadlineAction;
use DateTimeInterface;
use InvalidArgumentException;

final class SlaDeadlineEvaluator
{
    public function evaluate(
        DateTimeInterface $dueAt,
        DateTimeInterface $now,
        ?DateTimeInterface $warningSentAt,
        ?DateTimeInterface $breachedAt,
        int $warningMinutes,
    ): SlaDeadlineAction {
        if ($warningMinutes < 0) {
            throw new InvalidArgumentException('The SLA warning threshold cannot be negative.');
        }

        if ($breachedAt !== null) {
            return SlaDeadlineAction::NONE;
        }

        if ($now >= $dueAt) {
            return SlaDeadlineAction::BREACHED;
        }

        $warningAt = (clone $dueAt)->modify(sprintf('-%d minutes', $warningMinutes));

        if ($warningSentAt === null && $now >= $warningAt) {
            return SlaDeadlineAction::WARNING;
        }

        return SlaDeadlineAction::NONE;
    }
}
