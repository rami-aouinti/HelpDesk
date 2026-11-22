<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Events;

use App\Events\TicketCreated;
use App\Http\Middleware\CacheApiGetResponse;
use App\Models\Ticket as EloquentTicket;
use App\ServiceDesk\Application\Contracts\TicketCreatedPublisher;
use App\ServiceDesk\Domain\Events\TicketWasCreated;

final class LaravelTicketCreatedPublisher implements TicketCreatedPublisher
{
    public function publish(TicketWasCreated $event): void
    {
        $ticket = EloquentTicket::query()->findOrFail($event->ticketId);

        // Keep existing notification and Elasticsearch listeners during migration.
        event(new TicketCreated($ticket));
        CacheApiGetResponse::flush();
    }
}
