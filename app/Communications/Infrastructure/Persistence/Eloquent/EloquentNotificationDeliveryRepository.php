<?php

declare(strict_types=1);

namespace App\Communications\Infrastructure\Persistence\Eloquent;

use App\Communications\Application\Contracts\NotificationDeliveryRepository;
use App\Communications\Domain\Enums\NotificationDeliveryStatus;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

final class EloquentNotificationDeliveryRepository implements NotificationDeliveryRepository
{
    public function recordAttempt(
        string $notificationId,
        string $notifiableType,
        string $notifiableId,
        string $notificationType,
        string $channel,
        DateTimeInterface $occurredAt,
    ): void {
        DB::transaction(function () use (
            $notificationId,
            $notifiableType,
            $notifiableId,
            $notificationType,
            $channel,
            $occurredAt,
        ): void {
            $delivery = DB::table('notification_deliveries')
                ->where('notification_id', $notificationId)
                ->where('notifiable_type', $notifiableType)
                ->where('notifiable_id', $notifiableId)
                ->where('channel', $channel)
                ->lockForUpdate()
                ->first();

            if ($delivery === null) {
                DB::table('notification_deliveries')->insert([
                    'notification_id' => $notificationId,
                    'notifiable_type' => $notifiableType,
                    'notifiable_id' => $notifiableId,
                    'notification_type' => $notificationType,
                    'channel' => $channel,
                    'status' => NotificationDeliveryStatus::Pending->value,
                    'attempts' => 1,
                    'last_attempt_at' => $occurredAt,
                    'created_at' => $occurredAt,
                    'updated_at' => $occurredAt,
                ]);

                return;
            }

            DB::table('notification_deliveries')
                ->where('id', $delivery->id)
                ->update([
                    'notification_type' => $notificationType,
                    'status' => NotificationDeliveryStatus::Pending->value,
                    'attempts' => DB::raw('attempts + 1'),
                    'last_attempt_at' => $occurredAt,
                    'last_error' => null,
                    'sent_at' => null,
                    'failed_at' => null,
                    'updated_at' => $occurredAt,
                ]);
        });
    }

    public function recordSent(
        string $notificationId,
        string $notifiableType,
        string $notifiableId,
        string $channel,
        DateTimeInterface $occurredAt,
    ): void {
        DB::table('notification_deliveries')
            ->where('notification_id', $notificationId)
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->where('channel', $channel)
            ->update([
                'status' => NotificationDeliveryStatus::Sent->value,
                'sent_at' => $occurredAt,
                'failed_at' => null,
                'last_error' => null,
                'updated_at' => $occurredAt,
            ]);
    }

    public function recordFailed(
        string $notificationId,
        string $notifiableType,
        string $notifiableId,
        string $channel,
        string $error,
        DateTimeInterface $occurredAt,
    ): void {
        DB::table('notification_deliveries')
            ->where('notification_id', $notificationId)
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->where('channel', $channel)
            ->update([
                'status' => NotificationDeliveryStatus::Failed->value,
                'failed_at' => $occurredAt,
                'last_error' => mb_substr($error, 0, 4000),
                'updated_at' => $occurredAt,
            ]);
    }
}
