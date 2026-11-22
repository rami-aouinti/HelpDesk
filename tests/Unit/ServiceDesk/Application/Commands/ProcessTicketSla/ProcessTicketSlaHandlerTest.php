<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceDesk\Application\Commands\ProcessTicketSla;

use App\ServiceDesk\Application\Commands\ProcessTicketSla\ProcessTicketSlaCommand;
use App\ServiceDesk\Application\Commands\ProcessTicketSla\ProcessTicketSlaHandler;
use App\ServiceDesk\Application\Contracts\TicketSlaProcessor;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ProcessTicketSlaHandlerTest extends TestCase
{
    public function test_it_delegates_processing_to_the_sla_port(): void
    {
        $processor = $this->createMock(TicketSlaProcessor::class);
        $processor
            ->expects(self::once())
            ->method('process')
            ->with(42);

        $handler = new ProcessTicketSlaHandler($processor);
        $handler->handle(new ProcessTicketSlaCommand(42));
    }

    public function test_command_rejects_an_invalid_ticket_identifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ProcessTicketSlaCommand(0);
    }
}
