<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\TicketCreated;
use App\Listeners\IndexTicketInElasticsearch;
use App\Models\Ticket;
use Illuminate\Console\Command;

class ReindexTicketsCommand extends Command
{
    protected $signature = 'tickets:reindex {--chunk=200 : Number of tickets loaded per batch}';

    protected $description = 'Rebuild the Elasticsearch ticket search projection from PostgreSQL.';

    public function handle(IndexTicketInElasticsearch $indexer): int
    {
        if (! config('services.elasticsearch.enabled')) {
            $this->error('Elasticsearch ticket indexing is disabled.');

            return self::FAILURE;
        }

        $chunkSize = max(1, (int) $this->option('chunk'));
        $indexed = 0;

        Ticket::query()->chunkById($chunkSize, function ($tickets) use ($indexer, &$indexed): void {
            foreach ($tickets as $ticket) {
                $indexer->handle(new TicketCreated($ticket));
                $indexed++;
            }
        });

        $this->info("Indexed {$indexed} tickets into Elasticsearch.");

        return self::SUCCESS;
    }
}
