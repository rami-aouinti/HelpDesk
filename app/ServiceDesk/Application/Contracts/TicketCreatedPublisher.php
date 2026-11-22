<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\ServiceDesk\Domain\Events\TicketWasCreated;

interface TicketCreatedPublisher
{
    public function publish(TicketWasCreated $event): void;
}
