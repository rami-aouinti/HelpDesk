<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceDesk\Domain;

use App\ServiceDesk\Domain\Entities\Ticket;
use App\ServiceDesk\Domain\Enums\TicketPriority;
use App\ServiceDesk\Domain\Enums\TicketStatus;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketData;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketTransition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
    #[Test]
    public function it_can_change_ticket_priority(): void
    {
        $ticket = Ticket::reconstitute(
            id: 105,
            subject: 'Slow application',
            description: 'The application responds slowly.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
            status: TicketStatus::OPEN,
        );

        $ticket->changePriority(TicketPriority::URGENT);

        self::assertSame(TicketPriority::URGENT, $ticket->priority());
    }

    #[Test]
    public function it_opens_a_ticket_with_the_expected_initial_state(): void
    {
        $ticket = Ticket::open(
            subject: '  Cannot login  ',
            description: '  The sign-in page returns an error.  ',
            priority: TicketPriority::HIGH,
            projectId: 12,
            customerId: 34,
        );

        self::assertNull($ticket->id());
        self::assertSame('Cannot login', $ticket->subject());
        self::assertSame('The sign-in page returns an error.', $ticket->description());
        self::assertSame(TicketPriority::HIGH, $ticket->priority());
        self::assertSame(TicketStatus::OPEN, $ticket->status());
        self::assertSame(12, $ticket->projectId());
        self::assertSame(34, $ticket->customerId());
    }

    #[Test]
    public function it_allows_a_generated_identifier_to_be_assigned_once(): void
    {
        $ticket = Ticket::open(
            subject: 'Cannot login',
            description: 'The sign-in page returns an error.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
        );

        $ticket->assignId(101);

        self::assertSame(101, $ticket->id());
    }

    #[Test]
    public function an_administrator_can_assign_a_ticket_to_an_agent_and_team(): void
    {
        $ticket = Ticket::open(
            subject: 'Cannot login',
            description: 'The sign-in page returns an error.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
        );

        $ticket->assignToAgent(agentId: 52, teamId: 7);

        self::assertSame(7, $ticket->teamId());
        self::assertSame(52, $ticket->assignedAgentId());
    }

    #[Test]
    public function assigning_a_ticket_rejects_invalid_identifiers(): void
    {
        $ticket = Ticket::open(
            subject: 'Cannot login',
            description: 'The sign-in page returns an error.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
        );

        $this->expectException(InvalidTicketData::class);

        $ticket->assignToAgent(agentId: 0, teamId: 7);
    }

    #[Test]
    public function an_agent_can_claim_an_unassigned_ticket_for_a_team(): void
    {
        $ticket = Ticket::open(
            subject: 'Cannot login',
            description: 'The sign-in page returns an error.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
        );

        $ticket->claimBy(agentId: 52, teamId: 7);

        self::assertSame(7, $ticket->teamId());
        self::assertSame(52, $ticket->assignedAgentId());
        self::assertSame(TicketStatus::IN_PROGRESS, $ticket->status());
    }

    #[Test]
    public function claiming_a_ticket_already_assigned_is_rejected(): void
    {
        $ticket = Ticket::reconstitute(
            id: 101,
            subject: 'Cannot login',
            description: 'The sign-in page returns an error.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
            status: TicketStatus::IN_PROGRESS,
            teamId: 7,
            assignedAgentId: 52,
        );

        $this->expectException(InvalidTicketData::class);
        $this->expectExceptionMessage('This ticket has already been assigned');

        $ticket->claimBy(agentId: 53, teamId: 7);
    }

    #[Test]
    public function a_ticket_cannot_be_claimed_for_a_different_team(): void
    {
        $ticket = Ticket::reconstitute(
            id: 101,
            subject: 'Cannot login',
            description: 'The sign-in page returns an error.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
            status: TicketStatus::OPEN,
            teamId: 7,
            assignedAgentId: null,
        );

        $this->expectException(InvalidTicketData::class);
        $this->expectExceptionMessage('cannot be claimed by another team');

        $ticket->claimBy(agentId: 52, teamId: 8);
    }

    #[Test]
    public function claiming_an_open_ticket_keeps_non_open_status_unchanged(): void
    {
        $ticket = Ticket::reconstitute(
            id: 101,
            subject: 'Waiting for customer',
            description: 'A response is expected.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
            status: TicketStatus::WAITING_FOR_CUSTOMER,
        );

        $ticket->claimBy(agentId: 52, teamId: 7);

        self::assertSame(TicketStatus::WAITING_FOR_CUSTOMER, $ticket->status());
    }

    #[Test]
    public function it_allows_only_valid_agent_status_transitions(): void
    {
        $ticket = Ticket::reconstitute(
            id: 102,
            subject: 'Printer offline',
            description: 'The printer is unreachable.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
            status: TicketStatus::OPEN,
        );

        $ticket->changeStatus(TicketStatus::IN_PROGRESS);

        self::assertSame(TicketStatus::IN_PROGRESS, $ticket->status());
    }

    #[Test]
    public function it_rejects_invalid_agent_status_transitions(): void
    {
        $ticket = Ticket::reconstitute(
            id: 103,
            subject: 'Resolved request',
            description: 'The issue has been resolved.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
            status: TicketStatus::RESOLVED,
        );

        $this->expectException(InvalidTicketTransition::class);

        $ticket->changeStatus(TicketStatus::IN_PROGRESS);
    }

    #[Test]
    public function an_administrator_can_override_the_status_workflow(): void
    {
        $ticket = Ticket::reconstitute(
            id: 104,
            subject: 'Closed request',
            description: 'The request was closed.',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
            status: TicketStatus::CLOSED,
        );

        $ticket->changeStatus(TicketStatus::OPEN, mayOverrideWorkflow: true);

        self::assertSame(TicketStatus::OPEN, $ticket->status());
    }

    #[Test]
    public function it_rejects_an_empty_subject(): void
    {
        $this->expectException(InvalidTicketData::class);

        Ticket::open(
            subject: '  ',
            description: 'A valid description',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 34,
        );
    }

    #[Test]
    public function it_rejects_an_invalid_customer_identifier(): void
    {
        $this->expectException(InvalidTicketData::class);

        Ticket::open(
            subject: 'Cannot login',
            description: 'A valid description',
            priority: TicketPriority::NORMAL,
            projectId: null,
            customerId: 0,
        );
    }
}
