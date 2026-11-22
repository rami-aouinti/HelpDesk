<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketMessageRepository
{
    public function create(int $ticketId, int $authorId, string $body, bool $isInternal): int;
}
