<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Persistence\Eloquent;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\ServiceDesk\Application\Contracts\TicketSlaRepository;
use App\ServiceDesk\Application\DTO\TicketSlaSnapshot;
use DateTimeImmutable;
use DateTimeInterface;

final class EloquentTicketSlaRepository implements TicketSlaRepository
{
    public function eachActiveTicketId(callable $callback, int $chunkSize = 100): void
    {
        $statuses = config('helpdesk.sla.active_statuses', []);

        if ($statuses === [] || $chunkSize < 1) {
            return;
        }

        Ticket::query()
            ->whereIn('status', $statuses)
            ->select('id')
            ->chunkById($chunkSize, static function ($tickets) use ($callback): void {
                $callback($tickets->map(static fn (Ticket $ticket): int => (int) $ticket->getKey())->all());
            });
    }

    public function findForProcessing(int $ticketId): ?TicketSlaSnapshot
    {
        $ticket = Ticket::query()->find($ticketId);

        if ($ticket === null) {
            return null;
        }

        $firstPublicStaffReply = $ticket->messages()
            ->where('is_internal', false)
            ->with('user')
            ->oldest('created_at')
            ->get()
            ->first(fn (TicketMessage $message): bool => ! $message->user?->hasRole('customer'));

        return new TicketSlaSnapshot(
            ticketId: (int) $ticket->getKey(),
            priority: (string) $ticket->priority,
            status: (string) $ticket->status,
            createdAt: $this->immutable($ticket->created_at),
            firstResponseAt: $this->nullableImmutable($ticket->first_response_at),
            firstPublicStaffReplyAt: $firstPublicStaffReply
                ? $this->nullableImmutable($firstPublicStaffReply->created_at)
                : null,
            firstResponseDueAt: $this->nullableImmutable($ticket->first_response_due_at),
            firstResponseWarningSentAt: $this->nullableImmutable($ticket->first_response_warning_sent_at),
            firstResponseBreachedAt: $this->nullableImmutable($ticket->first_response_breached_at),
            resolutionDueAt: $this->nullableImmutable($ticket->resolution_due_at),
            resolutionWarningSentAt: $this->nullableImmutable($ticket->resolution_warning_sent_at),
            resolutionBreachedAt: $this->nullableImmutable($ticket->resolution_breached_at),
        );
    }

    public function initializeDeadlines(
        int $ticketId,
        DateTimeImmutable $firstResponseDueAt,
        DateTimeImmutable $resolutionDueAt,
    ): void {
        Ticket::query()
            ->whereKey($ticketId)
            ->whereNull('first_response_due_at')
            ->update(['first_response_due_at' => $firstResponseDueAt]);

        Ticket::query()
            ->whereKey($ticketId)
            ->whereNull('resolution_due_at')
            ->update(['resolution_due_at' => $resolutionDueAt]);
    }

    public function recordFirstResponse(int $ticketId, DateTimeImmutable $respondedAt): void
    {
        Ticket::query()
            ->whereKey($ticketId)
            ->whereNull('first_response_at')
            ->update(['first_response_at' => $respondedAt]);
    }

    public function markAlertSent(
        int $ticketId,
        string $slaType,
        string $state,
        DateTimeImmutable $sentAt,
    ): bool {
        $column = match ([$slaType, $state]) {
            ['first_response', 'warning'] => 'first_response_warning_sent_at',
            ['first_response', 'breached'] => 'first_response_breached_at',
            ['resolution', 'warning'] => 'resolution_warning_sent_at',
            ['resolution', 'breached'] => 'resolution_breached_at',
            default => throw new InvalidArgumentException('Unsupported SLA alert marker.'),
        };

        return Ticket::query()
            ->whereKey($ticketId)
            ->whereNull($column)
            ->update([$column => $sentAt]) === 1;
    }

    private function immutable(DateTimeInterface $date): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($date);
    }

    private function nullableImmutable(?DateTimeInterface $date): ?DateTimeImmutable
    {
        return $date === null ? null : $this->immutable($date);
    }
}
