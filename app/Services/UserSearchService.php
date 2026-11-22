<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserSearchService
{
    /**
     * @return array<int, int>|null Null means Elasticsearch is unavailable and SQL must be used.
     */
    public function searchIds(string $term): ?array
    {
        $term = trim($term);
        $endpoint = rtrim((string) config('services.elasticsearch.url'), '/');

        if ($term === '') {
            return [];
        }

        if (! config('services.elasticsearch.enabled') || config('app.env') === 'testing' || $endpoint === '') {
            return null;
        }

        try {
            $hits = Http::acceptJson()->timeout(2)
                ->get($endpoint . '/helpdesk-users/_search', [
                    'size' => 100,
                    'query' => [
                        'multi_match' => [
                            'query' => $term,
                            'fields' => ['name^3', 'email^2', 'roles'],
                            'type' => 'best_fields',
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
            Log::warning('elasticsearch.user_search_unavailable', [
                'exception' => $exception::class,
            ]);

            return null;
        }
    }

    /** @return Collection<int, User> */
    public function search(string $term): Collection
    {
        $ids = $this->searchIds($term);

        return User::query()
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->when($ids === null, function ($query) use ($term): void {
                $query->where(function ($nested) use ($term): void {
                    $nested->where('name', 'like', '%' . $term . '%')
                        ->orWhere('email', 'like', '%' . $term . '%');
                });
            })
            ->with('roles')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'email', 'is_active']);
    }
}
