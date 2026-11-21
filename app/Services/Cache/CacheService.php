<?php

declare(strict_types=1);

namespace App\Services\Cache;

use Closure;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Remember an expensive read result in Redis for five minutes by default.
     *
     * Callers must include relevant tenant/user/filter identifiers in the key.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $resolver
     * @return TValue
     */
    public function remember(string $key, Closure $resolver, ?int $ttlSeconds = null): mixed
    {
        $ttl = max(1, $ttlSeconds ?? (int) config('cache.default_ttl', 300));

        return Cache::remember($key, now()->addSeconds($ttl), $resolver);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }
}
