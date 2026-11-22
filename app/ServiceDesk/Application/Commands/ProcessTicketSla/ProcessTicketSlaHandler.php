<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\ProcessTicketSla;

use App\ServiceDesk\Application\Contracts\SlaPolicyProvider;
use App\ServiceDesk\Application\Contracts\TicketSlaRepository;
use App\ServiceDesk\Application\DTO\SlaAlert;
use App\ServiceDesk\Application\DTO\TicketSlaSnapshot;
use App\ServiceDesk\Domain\Enums\SlaDeadlineAction;
use App\ServiceDesk\Domain\Services\SlaDeadlineEvaluator;
use DateTimeImmutable;
use DateTimeInterface;

final class ProcessTicketSlaHandler
{
    public function __construct(
        private readonly TicketSlaRepository $tickets,
        private readonly SlaPolicyProvider $policies,
        private readonly SlaDeadlineEvaluator $deadlineEvaluator,
    ) {
    }

    public function handle(ProcessTicketSlaCommand $command): void
    {
        $this->process($command->ticketId);
    }

    public function process(int $ticketId, ?DateTimeInterface $now = null): void
    {
        $ticket = $this->tickets->findForProcessing($ticketId);

        if (! $ticket instanceof \App\ServiceDesk\Application\DTO\TicketSlaSnapshot) {
            return;
        }

        $policy = $this->policies->forPriority($ticket->priority);

        if (! $policy instanceof \App\ServiceDesk\Domain\ValueObjects\SlaPolicy) {
            return;
        }

        $now = $now === null ? new DateTimeImmutable() : DateTimeImmutable::createFromInterface($now);

        if ($ticket->firstResponseDueAt === null || $ticket->resolutionDueAt === null) {
            $this->tickets->initializeDeadlines(
                $ticketId,
                $ticket->createdAt->modify(sprintf('+%d minutes', $policy->firstResponseMinutes)),
                $ticket->createdAt->modify(sprintf('+%d minutes', $policy->resolutionMinutes)),
            );
            $ticket = $this->tickets->findForProcessing($ticketId);

            if ($ticket === null) {
                return;
            }
        }

        if ($ticket->firstResponseAt === null && $ticket->firstPublicStaffReplyAt !== null) {
            $this->tickets->recordFirstResponse($ticketId, $ticket->firstPublicStaffReplyAt);
            $ticket = $this->tickets->findForProcessing($ticketId);

            if ($ticket === null) {
                return;
            }
        }

        if ($ticket->firstResponseAt === null && $ticket->firstResponseDueAt !== null) {
            $this->evaluateDeadline($ticket, 'first_response', $ticket->firstResponseDueAt, $ticket->firstResponseWarningSentAt, $ticket->firstResponseBreachedAt, $policy->warningMinutes, $now);
        }

        if ($policy->tracksResolutionFor($ticket->status) && $ticket->resolutionDueAt !== null) {
            $this->evaluateDeadline($ticket, 'resolution', $ticket->resolutionDueAt, $ticket->resolutionWarningSentAt, $ticket->resolutionBreachedAt, $policy->warningMinutes, $now);
        }
    }

    private function evaluateDeadline(
        TicketSlaSnapshot $ticket,
        string $type,
        DateTimeImmutable $dueAt,
        ?DateTimeImmutable $warningSentAt,
        ?DateTimeImmutable $breachedAt,
        int $warningMinutes,
        DateTimeImmutable $now,
    ): void {
        $action = $this->deadlineEvaluator->evaluate($dueAt, $now, $warningSentAt, $breachedAt, $warningMinutes);

        if ($action === SlaDeadlineAction::NONE) {
            return;
        }

        $this->tickets->enqueueAlertIfNotRecorded(
            new SlaAlert(
                ticketId: $ticket->ticketId,
                slaType: $type,
                state: $action === SlaDeadlineAction::BREACHED ? 'breached' : 'warning',
                dueAt: $dueAt,
            ),
            $now,
        );
    }
}
