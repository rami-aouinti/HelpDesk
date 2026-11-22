<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\IndexUserInElasticsearch;
use App\Models\User;
use Illuminate\Console\Command;

class ReindexUsersInElasticsearch extends Command
{
    protected $signature = 'elasticsearch:reindex-users {--chunk=100 : Number of users queued per batch}';

    protected $description = 'Queue all existing users for Elasticsearch indexing';

    public function handle(): int
    {
        if (! config('services.elasticsearch.enabled')) {
            $this->components->error('Elasticsearch indexing is disabled. Set ELASTICSEARCH_ENABLED=true.');

            return self::FAILURE;
        }

        $chunkSize = max(1, min(1000, (int) $this->option('chunk')));
        $queued = 0;

        User::query()->select('id')->chunkById($chunkSize, function ($users) use (&$queued): void {
            foreach ($users as $user) {
                IndexUserInElasticsearch::dispatch((int) $user->getKey())->afterCommit();
                $queued++;
            }
        });

        $this->components->info("Queued {$queued} users for Elasticsearch indexing.");

        return self::SUCCESS;
    }
}
