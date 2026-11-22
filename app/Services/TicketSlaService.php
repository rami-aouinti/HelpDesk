<?php

namespace App\Services;

use App\Models\Ticket;
use App\ServiceDesk\Application\Contracts\SlaAlertPublisher;
use App\ServiceDesk\Application\DTO\SlaAlert;
use App\ServiceDesk\Domain\Enums\SlaDeadlineAction;
use App\ServiceDesk\Domain\Services\SlaDeadlineEvaluator;
use App\ServiceDesk\Domain\ValueObjects\SlaPolicy;
use App\Models\TicketMessage;
use Illuminate\Support\Carbon;

class TicketSlaService
{
    public function __construct(
        private readonly SlaDeadlineEvaluator $deadlineEvaluator,
        private readonly SlaAlertPublisher $alertPublisher,
    ) {
    }

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

        $policy = new SlaPolicy(
            firstResponseMinutes: (int) $targets['first_response'],
            resolutionMinutes: (int) $targets['resolution'],
        );

        $updates = [];
        if (! $ticket->first_response_due_at) {
            $updates['first_response_due_at'] = $ticket->created_at->copy()->addMinutes($policy->firstResponseMinutes);
        }
        if (! $ticket->resolution_due_at) {
            $updates['resolution_due_at'] = $ticket->created_at->copy()->addMinutes($policy->resolutionMinutes);
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

        $action = $this->deadlineEvaluator->evaluate(
            dueAt: $dueAt,
            now: $now,
            warningSentAt: $ticket->{$warningColumn},
            breachedAt: $ticket->{$breachColumn},
            warningMinutes: (int) config('helpdesk.sla.warning_minutes', 60),
        );

        if ($action === SlaDeadlineAction::BREACHED) {
            $ticket->forceFill([$breachColumn => $now])->save();
            $this->publishAlert($ticket, $type, 'breached', $dueAt);

            return;
        }

        if ($action === SlaDeadlineAction::WARNING) {
            $ticket->forceFill([$warningColumn => $now])->save();
            $this->publishAlert($ticket, $type, 'warning', $dueAt);
        }
    }

    private function publishAlert(Ticket $ticket, string $type, string $state, Carbon $dueAt): void
    {
        $this->alertPublisher->publish(new SlaAlert(
            ticketId: (int) $ticket->getKey(),
            slaType: $type,
            state: $state,
            dueAt: $dueAt->toDateTimeImmutable(),
        ));
    }
}
