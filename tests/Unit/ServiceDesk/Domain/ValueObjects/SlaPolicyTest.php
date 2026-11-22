<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceDesk\Domain\ValueObjects;

use App\ServiceDesk\Domain\ValueObjects\SlaPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SlaPolicyTest extends TestCase
{
    public function test_it_tracks_resolution_only_for_unresolved_statuses(): void
    {
        $policy = new SlaPolicy(15, 240, 30, ['resolved', 'closed']);

        self::assertTrue($policy->tracksResolutionFor('open'));
        self::assertFalse($policy->tracksResolutionFor('resolved'));
        self::assertFalse($policy->tracksResolutionFor('closed'));
    }

    public function test_it_rejects_a_negative_warning_threshold(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SlaPolicy(15, 240, -1);
    }
}
