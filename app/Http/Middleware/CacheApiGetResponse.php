<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheApiGetResponse
{
    private const string CACHE_TAG = 'helpdesk:api:tickets';

    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $request->isMethod('GET')
            || ! $request->user()
            || app()->environment('testing')
            || ! self::supportsTagging()
        ) {
            return $next($request);
        }

        $query = $request->query();
        ksort($query);

        $key = 'api:get:' . hash('sha256', json_encode([
            'user_id' => $request->user()->getAuthIdentifier(),
            'path' => $request->path(),
            'query' => $query,
            'accept' => $request->header('Accept'),
        ], JSON_THROW_ON_ERROR));

        $cacheMiss = false;
        $uncachedResponse = null;

        $cached = Cache::tags([self::CACHE_TAG])->remember(
            $key,
            now()->addSeconds(max(1, (int) config('cache.default_ttl', 300))),
            function () use ($next, $request, &$cacheMiss, &$uncachedResponse): ?array {
                $cacheMiss = true;
                $response = $next($request);

                if (
                    $response->getStatusCode() !== Response::HTTP_OK
                    || ! str_contains((string) $response->headers->get('Content-Type'), 'application/json')
                    || $response->headers->has('Set-Cookie')
                ) {
                    $uncachedResponse = $response;

                    return null;
                }

                return [
                    'content' => $response->getContent(),
                    'status' => $response->getStatusCode(),
                ];
            },
        );

        if ($uncachedResponse instanceof Response) {
            return $uncachedResponse;
        }

        if (! is_array($cached)) {
            return $next($request);
        }

        return response($cached['content'], $cached['status'], [
            'Content-Type' => 'application/json',
            'X-HelpDesk-Cache' => $cacheMiss ? 'MISS' : 'HIT',
        ]);
    }

    public static function flush(): void
    {
        if (self::supportsTagging()) {
            Cache::tags([self::CACHE_TAG])->flush();
        }
    }

    private static function supportsTagging(): bool
    {
        return Cache::getStore() instanceof TaggableStore;
    }
}
