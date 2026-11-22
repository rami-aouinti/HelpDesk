<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\CreateTicket;

use App\ServiceDesk\Application\Contracts\TicketCreatedPublisher;
use App\ServiceDesk\Domain\Entities\Ticket;
use App\ServiceDesk\Domain\Events\TicketWasCreated;
use App\ServiceDesk\Domain\Repositories\TicketRepository;
use LogicException;

final readonly class CreateTicketHandler
{
    public function __construct(
        private TicketRepository $tickets,
        private TicketCreatedPublisher $events,
    ) {
    }

    public function handle(CreateTicketCommand $command): int
    {
        $ticket = Ticket::open(
            subject: $command->subject,
            description: $command->description,
            priority: $command->priority,
            projectId: $command->projectId,
            customerId: $command->customerId,
        );

        $this->tickets->save($ticket);

        $ticketId = $ticket->id();

        if ($ticketId === null) {
            throw new LogicException('The ticket repository did not assign an identifier.');
        }

        $this->events->publish(new TicketWasCreated($ticketId, $ticket->customerId()));

        return $ticketId;
    }
}
