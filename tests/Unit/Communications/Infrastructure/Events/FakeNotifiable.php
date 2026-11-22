<?php

declare(strict_types=1);

namespace Tests\Unit\Communications\Infrastructure\Events;

final class FakeNotifiable
{
    public function getMorphClass(): string
    {
        return 'users';
    }

    public function getAuthIdentifier(): int
    {
        return 42;
    }
}
