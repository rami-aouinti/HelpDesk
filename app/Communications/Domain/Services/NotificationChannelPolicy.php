<?php

declare(strict_types=1);

namespace App\Communications\Domain\Services;

/**
 * Decides which delivery capabilities are enabled for a recipient.
 *
 * This policy deliberately knows nothing about Laravel channel names,
 * notifications, queues or persistence.
 */
final readonly class NotificationChannelPolicy
{
    public function __construct(
        private bool $inAppEnabled,
        private bool $emailEnabled,
    ) {
    }

    public function shouldDeliverInApp(): bool
    {
        return $this->inAppEnabled;
    }

    public function shouldDeliverEmail(): bool
    {
        return $this->emailEnabled;
    }
}
