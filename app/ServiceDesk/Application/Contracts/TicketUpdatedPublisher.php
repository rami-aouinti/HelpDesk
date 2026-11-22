<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\ServiceDesk\Domain\Events\TicketWasUpdated;

interface TicketUpdatedPublisher
{
    public function publish(TicketWasUpdated $event): void;
}
