<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceDesk\Domain;

use App\ServiceDesk\Domain\Entities\Ticket;
use App\ServiceDesk\Domain\Enums\TicketPriority;
use App\ServiceDesk\Domain\Enums\TicketStatus;
use App\ServiceDesk\Domain\Exceptions\InvalidTicketData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
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
