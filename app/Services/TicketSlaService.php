<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\TicketSlaAlertNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class TicketSlaService
{
    /**
     * Initialize SLA deadlines and process one ticket idempotently.
     */
    public function process(Ticket $ticket, ?Carbon $now = null): void
    {
        $now ??= now();
        $targets = config('helpdesk.sla.priorities.' . $ticket->priority);

        if (! is_array($targets)) {
            return;
        }

        $updates = [];
        if (! $ticket->first_response_due_at) {
            $updates['first_response_due_at'] = $ticket->created_at->copy()->addMinutes((int) $targets['first_response']);
        }
        if (! $ticket->resolution_due_at) {
            $updates['resolution_due_at'] = $ticket->created_at->copy()->addMinutes((int) $targets['resolution']);
        }

        if ($updates !== []) {
            $ticket->forceFill($updates)->save();
            $ticket->refresh();
        }

        if (! $ticket->first_response_at) {
            $firstPublicStaffReply = $ticket->messages()
                ->where('is_internal', false)
                ->with('user')
                ->oldest('created_at')
                ->get()
                ->first(fn (TicketMessage $message): bool => ! $message->user?->hasRole('customer'));

            if ($firstPublicStaffReply) {
                $ticket->forceFill(['first_response_at' => $firstPublicStaffReply->created_at])->save();
                $ticket->refresh();
            }
        }

        if (! $ticket->first_response_at) {
            $this->evaluateDeadline(
                $ticket,
                'first_response',
                'first_response_due_at',
                'first_response_warning_sent_at',
                'first_response_breached_at',
                $now,
            );
        }

        if (! in_array($ticket->status, config('helpdesk.sla.resolved_statuses', []), true)) {
            $this->evaluateDeadline(
                $ticket,
                'resolution',
                'resolution_due_at',
                'resolution_warning_sent_at',
                'resolution_breached_at',
                $now,
            );
        }
    }

    private function evaluateDeadline(
        Ticket $ticket,
        string $type,
        string $dueColumn,
        string $warningColumn,
        string $breachColumn,
        Carbon $now,
    ): void {
        $dueAt = $ticket->{$dueColumn};
        if (! $dueAt) {
            return;
        }

        $warningAt = $dueAt->copy()->subMinutes((int) config('helpdesk.sla.warning_minutes', 60));

        if ($now->greaterThanOrEqualTo($dueAt)) {
            if (! $ticket->{$breachColumn}) {
                $ticket->forceFill([$breachColumn => $now])->save();
                $this->notify($ticket, $type, 'breached', $dueAt);
            }

            return;
        }

        if ($now->greaterThanOrEqualTo($warningAt) && ! $ticket->{$warningColumn}) {
            $ticket->forceFill([$warningColumn => $now])->save();
            $this->notify($ticket, $type, 'warning', $dueAt);
        }
    }

    private function notify(Ticket $ticket, string $type, string $state, Carbon $dueAt): void
    {
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
            new TicketSlaAlertNotification($ticket, $type, $state, $dueAt),
        );
    }
}
