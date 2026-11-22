<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\ServiceDesk\Domain\Events\TicketWasClaimed;

interface TicketClaimedPublisher
{
    public function publish(TicketWasClaimed $event): void;
}
