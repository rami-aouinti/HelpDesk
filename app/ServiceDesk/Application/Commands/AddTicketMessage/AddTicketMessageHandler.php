<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AddTicketMessage;

use App\Models\TicketMessage;
use App\ServiceDesk\Application\Contracts\TicketMessageCreatedPublisher;
use App\ServiceDesk\Application\Contracts\TicketMessageRepository;
use App\ServiceDesk\Application\Contracts\TransactionManager;

final class AddTicketMessageHandler
{
    public function __construct(
        private readonly TicketMessageRepository $messages,
        private readonly TicketMessageCreatedPublisher $publisher,
        private readonly TransactionManager $transactions,
    ) {
    }

    public function handle(AddTicketMessageCommand $command): TicketMessage
    {
        $message = $this->transactions->run(fn (): TicketMessage => $this->messages->create(
            $command->ticketId,
            $command->authorId,
            trim($command->body),
            $command->isInternal,
        ));

        $this->publisher->publish($message);

        return $message;
    }
}
