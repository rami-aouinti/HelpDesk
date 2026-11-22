<?php

declare(strict_types=1);

namespace App\Communications\Application\Contracts;

use DateTimeInterface;

interface NotificationDeliveryRepository
{
    public function recordAttempt(
        string $notificationId,
        string $notifiableType,
        string $notifiableId,
        string $notificationType,
        string $channel,
        DateTimeInterface $occurredAt,
    ): void;

    public function recordSent(
        string $notificationId,
        string $notifiableType,
        string $notifiableId,
        string $channel,
        DateTimeInterface $occurredAt,
    ): void;

    public function recordFailed(
        string $notificationId,
        string $notifiableType,
        string $notifiableId,
        string $channel,
        string $error,
        DateTimeInterface $occurredAt,
    ): void;
}
