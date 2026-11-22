<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Events;

use App\Events\TicketUpdated;
use App\Http\Middleware\CacheApiGetResponse;
use App\Models\Ticket as EloquentTicket;
use App\Models\User;
use App\ServiceDesk\Application\Contracts\TicketClaimedPublisher;
use App\ServiceDesk\Domain\Events\TicketWasClaimed;

final class LaravelTicketClaimedPublisher implements TicketClaimedPublisher
{
    public function publish(TicketWasClaimed $event): void
    {
        $ticket = EloquentTicket::query()
            ->with(['customer', 'assignedAgent', 'team'])
            ->findOrFail($event->ticketId);

        $actor = User::query()->findOrFail($event->agentId);

        // Preserve existing notification and Elasticsearch listeners.
        event(new TicketUpdated($ticket, $actor, $event->changes));
        CacheApiGetResponse::flush();
    }
}
