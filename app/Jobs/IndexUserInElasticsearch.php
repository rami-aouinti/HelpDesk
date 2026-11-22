<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class IndexUserInElasticsearch implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [5, 30, 60];

    public function __construct(public readonly int $userId)
    {
    }

    /**
     * Tags this job so Horizon can monitor user indexing separately.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['user-search-indexing'];
    }

    public function handle(): void
    {
        if (! config('services.elasticsearch.enabled') || config('app.env') === 'testing') {
            return;
        }

        $endpoint = rtrim((string) config('services.elasticsearch.url'), '/');
        if ($endpoint === '') {
            return;
        }

        $user = User::query()->with('roles')->find($this->userId);
        if (! $user) {
            $response = Http::timeout(5)->delete($endpoint . '/helpdesk-users/_doc/' . $this->userId);
            if (! $response->successful() && $response->status() !== 404) {
                $response->throw();
            }

            return;
        }

        Http::acceptJson()->timeout(5)
            ->put($endpoint . '/helpdesk-users/_doc/' . $user->getKey(), [
                'user_id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'roles' => $user->roles->pluck('name')->all(),
            ])
            ->throw();
    }
}
