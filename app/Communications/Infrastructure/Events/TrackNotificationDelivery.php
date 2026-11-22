<?php

declare(strict_types=1);

namespace App\Communications\Infrastructure\Events;

use App\Communications\Application\Contracts\NotificationDeliveryRepository;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Throwable;

final class TrackNotificationDelivery
{
    public function __construct(
        private readonly NotificationDeliveryRepository $deliveries,
    ) {
    }

    public function sending(NotificationSending $event): void
    {
        [$type, $id] = $this->notifiableIdentity($event->notifiable);

        $this->deliveries->recordAttempt(
            (string) $event->notification->id,
            $type,
            $id,
            $event->notification::class,
            (string) $event->channel,
            now(),
        );
    }

    public function sent(NotificationSent $event): void
    {
        [$type, $id] = $this->notifiableIdentity($event->notifiable);

        $this->deliveries->recordSent(
            (string) $event->notification->id,
            $type,
            $id,
            (string) $event->channel,
            now(),
        );
    }

    public function failed(NotificationFailed $event): void
    {
        [$type, $id] = $this->notifiableIdentity($event->notifiable);
        $exception = $event->data['exception'] ?? null;
        $error = $exception instanceof Throwable
            ? $exception::class . ': ' . $exception->getMessage()
            : 'Notification delivery failed.';

        $this->deliveries->recordFailed(
            (string) $event->notification->id,
            $type,
            $id,
            (string) $event->channel,
            $error,
            now(),
        );
    }

    /** @return array{string, string} */
    private function notifiableIdentity(object $notifiable): array
    {
        $type = method_exists($notifiable, 'getMorphClass')
            ? $notifiable->getMorphClass()
            : $notifiable::class;
        $id = method_exists($notifiable, 'getAuthIdentifier')
            ? $notifiable->getAuthIdentifier()
            : (method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null);

        if ($id === null) {
            throw new \InvalidArgumentException('A notification recipient must expose a stable identifier.');
        }

        return [(string) $type, (string) $id];
    }
}
