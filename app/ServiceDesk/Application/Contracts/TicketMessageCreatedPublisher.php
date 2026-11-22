<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketMessageCreatedPublisher
{
    public function publish(int $messageId): void;
}
