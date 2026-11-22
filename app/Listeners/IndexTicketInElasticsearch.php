<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;

class IndexTicketInElasticsearch implements ShouldQueue
{
    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 30, 60];

    /**
     * Tags this listener so Horizon can monitor ticket indexing separately.
     *
     * @return array<int, string>
     */
    public function tags(mixed $event = null): array
    {
        return ['ticket-search-indexing'];
    }

    public function handle(TicketCreated|TicketUpdated $event): void
    {
        $ticket = $event->ticket;
        $endpoint = rtrim((string) config('services.elasticsearch.url'), '/');

        if (! config('services.elasticsearch.enabled') || $endpoint === '' || config('app.env') === 'testing') {
            return;
        }

        Http::acceptJson()
            ->timeout(5)
            ->put($endpoint . '/helpdesk-tickets/_doc/' . $ticket->getKey(), [
                'ticket_id' => $ticket->getKey(),
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'team_id' => $ticket->team_id,
                'project_id' => $ticket->project_id,
                'assigned_to' => $ticket->assigned_to,
                'created_at' => $ticket->created_at?->toISOString(),
                'updated_at' => $ticket->updated_at?->toISOString(),
            ])
            ->throw();
    }
}
