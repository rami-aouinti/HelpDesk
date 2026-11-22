<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketSlaProcessor
{
    public function process(int $ticketId): void;
}
