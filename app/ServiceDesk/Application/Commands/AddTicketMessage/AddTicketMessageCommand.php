<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AddTicketMessage;

final readonly class AddTicketMessageCommand
{
    public function __construct(
        public int $ticketId,
        public int $authorId,
        public string $body,
        public bool $isInternal = false,
    ) {
    }
}
