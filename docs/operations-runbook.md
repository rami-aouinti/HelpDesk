# HelpDesk operations runbook

## Load testing with k6

The repository includes two k6 scripts:

- `k6/helpdesk-smoke.js`: lightweight availability/API smoke check.
- `k6/helpdesk-load.js`: staged load scenario that exercises the authenticated ticket list, ticket search and ticket details, with optional ticket creation.

### Prepare the local environment

Start the application and make sure the database is migrated and seeded:

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
```

Install k6 on the host (see the official k6 installation guide), then create Sanctum personal access tokens for seeded users. The API rate limiter is 120 requests per minute per authenticated user. For a meaningful 100-VU run, use tokens belonging to different users instead of sharing one token across all virtual users.

Example using Laravel Tinker to create tokens for the demo accounts:

```bash
docker compose exec app php artisan tinker --execute='$tokens = []; App\Models\User::query()->where("email", "like", "%@helpdesk.test")->orWhereIn("email", ["admin@helpdesk.test", "agent@helpdesk.test", "customer@helpdesk.test"])->orderBy("id")->each(function ($user) use (&$tokens) { if ($user->is_active && $user->email_verified_at) { $tokens[] = $user->createToken("k6-load-test")->plainTextToken; } }); echo json_encode($tokens, JSON_THROW_ON_ERROR);'
```

Copy the JSON array printed by Tinker. Keep it private: these are real bearer credentials. Revoke the generated tokens after testing (or remove the `k6-load-test` tokens from the `personal_access_tokens` table in your local database).

### Run the staged load test

Pass the token array through an environment variable (do not commit it to Git):

```bash
export BASE_URL=http://localhost:8080
export K6_TOKENS_JSON='["token-for-user-1","token-for-user-2","token-for-user-3"]'
k6 run k6/helpdesk-load.js
```

The scenario ramps through 10, 25, 50 and 100 virtual users, holds each stage, then ramps down. Each VU selects a token by its VU number. Provide at least as many distinct user tokens as practical; with fewer tokens, reduce the peak VUs or increase the think time to avoid measuring the per-user rate limiter.

Default thresholds are deliberately explicit, not universal capacity guarantees:

- HTTP request failure rate below 1%.
- At least 99% of checks pass.
- Overall p95 below 1.2 seconds and p99 below 2.5 seconds.
- Ticket list, search and detail p95 below 1 second.

If the application runs on another host/port, override `BASE_URL`. To change the pacing, set `THINK_TIME_SECONDS` (minimum 1 second; default 7 seconds to stay within the API rate limit when using the seeded demo accounts).

### Include write traffic (optional)

By default, the scenario is read-focused and does not create data. To exercise ticket creation and the asynchronous event/notification/indexing pipeline as well:

```bash
CREATE_TICKETS=true k6 run k6/helpdesk-load.js
```

This creates one test ticket every five iterations per VU. It writes to the configured database and can enqueue notifications/indexing jobs. Use a disposable local/staging environment, never production without an approved test window, and clean up generated tickets/jobs afterward.

### PHP-FPM worker capacity

The PHP-FPM pool is tuned in the `php-base` stage of the root `Dockerfile`, so the settings are versioned and baked into the application image. The current local/staging profile uses:

- `pm = dynamic`
- `pm.max_children = 8` (maximum simultaneous PHP requests handled by this FPM pool)
- `pm.start_servers = 2`
- `pm.min_spare_servers = 2`
- `pm.max_spare_servers = 4`
- `pm.max_requests` remains at the upstream image default; do not change it without evidence of worker memory growth.

After changing the Dockerfile, rebuild and recreate only the app service:

```bash
docker compose build app
docker compose up -d --no-deps app
```

Validate the effective configuration and check the container logs for pool saturation:

```bash
docker compose exec app sh -lc '/usr/local/sbin/php-fpm -tt 2>&1 | grep -E "pm =|pm.max_children|pm.start_servers|pm.min_spare_servers|pm.max_spare_servers|pm.max_requests"'
docker compose logs --since=10m app
```

Look for the warning `server reached pm.max_children setting`. If it returns, do not raise the limit blindly: measure worker RSS, Docker/WSL available memory, and CPU pressure first. More workers increase memory usage, and they do not fix slow SQL, Elasticsearch contention, or CPU saturation. Re-run the same k6 profile after each controlled change and compare p95/p99, error rate, worker saturation and resource usage. These values are a conservative developer/staging starting point, not a production capacity guarantee.

### Read the results

k6 prints request rates, failures, checks, response-time percentiles and the custom ticket list/detail/create timings. During the run, watch the application and workers:

```bash
docker compose logs -f app queue
docker compose exec app php artisan horizon:status
docker compose --profile monitoring ps
```

Open Grafana at http://localhost:3001 and correlate the test window with container CPU/memory, PostgreSQL activity and Redis queue metrics. k6 client-side latency is not the same as server processing time. Record the hardware, dataset size, VU stages, token count and commit SHA when comparing runs.

Do not treat the 100-VU profile as a production capacity claim until it has been run repeatedly against a representative environment and dataset. Start with a lower VU count on a developer machine.

## PostgreSQL and uploads backup

Run from the project root with database environment variables available:

```bash
DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=helpdesk DB_USERNAME=helpdesk DB_PASSWORD='<secret>' UPLOADS_DIR=./storage/app bash ./scripts/backup.sh
```

The script creates a custom-format PostgreSQL dump, archives both `storage/app/private` and `storage/app/public`, writes SHA-256 checksums and removes backups older than the configured retention period. Store backups encrypted and off-host; local files alone are not disaster recovery.

## Restore drill

Restore is destructive and requires typing `RESTORE`:

```bash
DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=helpdesk DB_USERNAME=helpdesk DB_PASSWORD='<secret>' UPLOADS_DIR=./storage/app bash ./scripts/restore.sh backups/helpdesk-db-<timestamp>.dump backups/helpdesk-uploads-<timestamp>.tar.gz
```

Perform restore drills against an isolated database and directory first. A successful dump is not proof of recoverability.

## Access and concurrency

The existing feature tests cover customer ownership, team isolation, project membership, and authorization boundaries. Keep those tests in CI. Ticket claiming uses a database transaction and row lock; run concurrent claim tests against PostgreSQL (SQLite in-memory does not prove PostgreSQL lock behavior).

## Elasticsearch checks

In Kibana Dev Tools:

```http
GET _cat/indices/helpdesk-*?v
GET helpdesk-tickets/_count
GET helpdesk-logs-*/_search
```

If ticket indexing is delayed, inspect Laravel failed jobs and queue logs. PostgreSQL remains authoritative; reindex rather than editing Elasticsearch documents manually.
