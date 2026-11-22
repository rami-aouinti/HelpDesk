<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Events;

use App\Events\TicketMessageCreated;
use App\Models\TicketMessage;
use App\ServiceDesk\Application\Contracts\TicketMessageCreatedPublisher;

final class LaravelTicketMessageCreatedPublisher implements TicketMessageCreatedPublisher
{
    public function publish(TicketMessage $message): void
    {
        event(new TicketMessageCreated($message));
    }
}
