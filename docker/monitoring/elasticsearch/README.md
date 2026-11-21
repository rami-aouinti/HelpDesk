# HelpDesk Elasticsearch strategy

Elasticsearch stores operational telemetry, not business records.

## Indexed events

- `http.request`: every dynamic HTTP request (method, route/path, status, duration, environment).
- `http.request.exception`: request exceptions and exception class.
- `queue.job.started`, `queue.job.completed`, `queue.job.failed`: worker lifecycle, queue, connection, attempt and exception metadata.
- Container stdout/stderr: Nginx, PHP-FPM, queue, Redis and infrastructure logs collected by Filebeat.

Request query values, request bodies, cookies, authorization headers, session identifiers and user PII are deliberately excluded. Static health/favicon/robots requests are excluded from application request events.

## Kibana

Create a data view named `helpdesk-logs-*` with `@timestamp` as the time field. Useful dashboards:
- HTTP traffic: request count by method/path/status, p95 duration, 4xx/5xx trends.
- Workers: started/completed/failed jobs by queue and job class, attempts and exception messages.
- Runtime: container logs and correlated errors.

Filebeat ships Docker container logs to Elasticsearch. Keep retention bounded in production with an ILM policy and enable Elasticsearch authentication/TLS before exposing it outside localhost.

## Cache

Redis is the Laravel cache store. Use `Cache::remember($key, now()->addSeconds(config('cache.default_ttl')), $callback)` for read-through caches; the default TTL is 300 seconds. Do not cache authorization decisions or user-specific data under shared keys.
