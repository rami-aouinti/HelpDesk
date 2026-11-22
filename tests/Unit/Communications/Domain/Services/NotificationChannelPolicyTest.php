<?php

declare(strict_types=1);

namespace Tests\Unit\Communications\Domain\Services;

use App\Communications\Domain\Services\NotificationChannelPolicy;
use PHPUnit\Framework\TestCase;

final class NotificationChannelPolicyTest extends TestCase
{
    public function test_it_evaluates_in_app_and_email_preferences_independently(): void
    {
        $emailOnly = new NotificationChannelPolicy(inAppEnabled: false, emailEnabled: true);

        self::assertFalse($emailOnly->shouldDeliverInApp());
        self::assertTrue($emailOnly->shouldDeliverEmail());

        $inAppOnly = new NotificationChannelPolicy(inAppEnabled: true, emailEnabled: false);

        self::assertTrue($inAppOnly->shouldDeliverInApp());
        self::assertFalse($inAppOnly->shouldDeliverEmail());

        $disabled = new NotificationChannelPolicy(inAppEnabled: false, emailEnabled: false);

        self::assertFalse($disabled->shouldDeliverInApp());
        self::assertFalse($disabled->shouldDeliverEmail());
    }
}
