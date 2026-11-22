<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

use App\Models\TicketMessage;

interface TicketMessageCreatedPublisher
{
    public function publish(TicketMessage $message): void;
}
