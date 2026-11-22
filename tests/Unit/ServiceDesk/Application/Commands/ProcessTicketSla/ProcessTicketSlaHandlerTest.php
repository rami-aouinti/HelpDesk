<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceDesk\Application\Commands\ProcessTicketSla;

use App\ServiceDesk\Application\Commands\ProcessTicketSla\ProcessTicketSlaCommand;
use App\ServiceDesk\Application\Commands\ProcessTicketSla\ProcessTicketSlaHandler;
use App\ServiceDesk\Application\Contracts\SlaAlertPublisher;
use App\ServiceDesk\Application\Contracts\SlaPolicyProvider;
use App\ServiceDesk\Application\Contracts\TicketSlaRepository;
use App\ServiceDesk\Application\DTO\SlaAlert;
use App\ServiceDesk\Application\DTO\TicketSlaSnapshot;
use App\ServiceDesk\Domain\ValueObjects\SlaPolicy;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProcessTicketSlaHandlerTest extends TestCase
{
    public function test_it_records_and_publishes_a_breach_when_the_deadline_has_passed(): void
    {
        $now = new DateTimeImmutable();
        $dueAt = $now->modify('-5 minutes');
        $ticket = new TicketSlaSnapshot(
            ticketId: 42,
            priority: 'urgent',
            status: 'open',
            createdAt: $now->modify('-1 day'),
            firstResponseAt: null,
            firstPublicStaffReplyAt: null,
            firstResponseDueAt: $dueAt,
            firstResponseWarningSentAt: null,
            firstResponseBreachedAt: null,
            resolutionDueAt: $now->modify('+2 hours'),
            resolutionWarningSentAt: null,
            resolutionBreachedAt: null,
        );

        $repository = $this->createMock(TicketSlaRepository::class);
        $repository->expects(self::once())
            ->method('findForProcessing')
            ->with(42)
            ->willReturn($ticket);
        $repository->expects(self::once())
            ->method('markAlertSent')
            ->with(
                42,
                'first_response',
                'breached',
                self::callback(fn (DateTimeImmutable $value): bool => $value >= $now),
            )
            ->willReturn(true);

        $policies = $this->createMock(SlaPolicyProvider::class);
        $policies->expects(self::once())
            ->method('forPriority')
            ->with('urgent')
            ->willReturn(new SlaPolicy(15, 240, 60));

        $publisher = $this->createMock(SlaAlertPublisher::class);
        $publisher->expects(self::once())
            ->method('publish')
            ->with(self::callback(
                fn (SlaAlert $alert): bool => $alert->ticketId === 42
                    && $alert->slaType === 'first_response'
                    && $alert->state === 'breached'
                    && $alert->dueAt == $dueAt,
            ));

        $handler = new ProcessTicketSlaHandler(
            $repository,
            $policies,
            new \App\ServiceDesk\Domain\Services\SlaDeadlineEvaluator(),
            $publisher,
        );

        $handler->handle(new ProcessTicketSlaCommand(42));
    }

    public function test_it_ignores_a_ticket_that_no_longer_exists(): void
    {
        $repository = $this->createMock(TicketSlaRepository::class);
        $repository->expects(self::once())
            ->method('findForProcessing')
            ->with(42)
            ->willReturn(null);

        $policies = $this->createMock(SlaPolicyProvider::class);
        $policies->expects(self::never())->method('forPriority');

        $publisher = $this->createMock(SlaAlertPublisher::class);
        $publisher->expects(self::never())->method('publish');

        $handler = new ProcessTicketSlaHandler(
            $repository,
            $policies,
            new \App\ServiceDesk\Domain\Services\SlaDeadlineEvaluator(),
            $publisher,
        );

        $handler->handle(new ProcessTicketSlaCommand(42));
    }

    public function test_command_rejects_an_invalid_ticket_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProcessTicketSlaCommand(0);
    }
}
