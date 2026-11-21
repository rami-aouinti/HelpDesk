<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TicketSearchService
{
    /**
     * Return matching ticket IDs, or null when Elasticsearch is unavailable so SQL can be used.
     *
     * @return array<int, int>|null
     */
    public function searchIds(string $term): ?array
    {
        $term = trim($term);
        $endpoint = rtrim((string) config('services.elasticsearch.url'), '/');

        if ($term === '') {
            return [];
        }

        if (! config('services.elasticsearch.enabled') || app()->environment('testing') || $endpoint === '') {
            return null;
        }

        try {
            $hits = Http::acceptJson()
                ->timeout(2)
                ->get($endpoint . '/helpdesk-tickets/_search', [
                    'size' => 1000,
                    'query' => [
                        'multi_match' => [
                            'query' => $term,
                            'fields' => ['subject^3', 'description'],
                        ],
                    ],
                ])
                ->throw()
                ->json('hits.hits');

            if (! is_array($hits)) {
                return [];
            }

            $ids = [];
            foreach ($hits as $hit) {
                if (is_array($hit) && isset($hit['_id']) && is_numeric($hit['_id'])) {
                    $ids[] = (int) $hit['_id'];
                }
            }

            return array_values(array_unique($ids));
        } catch (Throwable $exception) {
            Log::warning('elasticsearch.ticket_search_unavailable', [
                'exception' => $exception::class,
            ]);

            return null;
        }
    }
}
