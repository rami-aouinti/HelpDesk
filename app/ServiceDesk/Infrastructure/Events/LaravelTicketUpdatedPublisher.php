<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Events;

use App\Events\TicketUpdated;
use App\Http\Middleware\CacheApiGetResponse;
use App\Models\Ticket as EloquentTicket;
use App\Models\User;
use App\ServiceDesk\Application\Contracts\TicketUpdatedPublisher;
use App\ServiceDesk\Domain\Events\TicketWasUpdated;

final class LaravelTicketUpdatedPublisher implements TicketUpdatedPublisher
{
    public function publish(TicketWasUpdated $event): void
    {
        $ticket = EloquentTicket::query()
            ->with(['customer', 'assignedAgent', 'team'])
            ->findOrFail($event->ticketId);

        $actor = User::query()->findOrFail($event->actorId);

        event(new TicketUpdated($ticket, $actor, $event->changes));
        CacheApiGetResponse::flush();
    }
}
