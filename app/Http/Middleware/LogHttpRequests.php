<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogHttpRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('up') || $request->is('favicon.ico') || $request->is('robots.txt')) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $status = 500;

        try {
            $response = $next($request);
            $status = $response->getStatusCode();

            return $response;
        } catch (Throwable $exception) {
            Log::error('http.request.exception', [
                'event' => 'http.request.exception',
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'route' => $request->route()?->getName(),
                'exception' => $exception::class,
            ]);

            throw $exception;
        } finally {
            Log::info('http.request', [
                'event' => 'http.request',
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'route' => $request->route()?->getName(),
                'status_code' => $status,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'environment' => app()->environment(),
            ]);
        }
    }
}
