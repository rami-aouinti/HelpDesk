<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Sla;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketSlaAlertNotification;
use App\ServiceDesk\Application\Contracts\SlaAlertPublisher;
use App\ServiceDesk\Application\DTO\SlaAlert;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

final class LaravelSlaAlertPublisher implements SlaAlertPublisher
{
    public function publish(SlaAlert $alert): void
    {
        $ticket = Ticket::query()->find($alert->ticketId);

        if ($ticket === null) {
            return;
        }

        $recipients = collect([$ticket->assignedAgent]);

        if ($ticket->team) {
            $recipients = $recipients->merge(
                $ticket->team->members()->where('is_active', true)->role('agent')->get(),
            );
        } elseif (! $ticket->assignedAgent) {
            $recipients = $recipients->merge(
                User::query()->role('agent')->where('is_active', true)->get(),
            );
        }

        $recipients = $recipients
            ->merge(User::query()->role('admin')->where('is_active', true)->get())
            ->filter(fn (?User $user): bool => $user instanceof User && $user->is_active)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new TicketSlaAlertNotification(
                $ticket,
                $alert->slaType,
                $alert->state,
                Carbon::instance($alert->dueAt),
            ),
        );
    }
}
