# Production deployment guide

This guide describes the production requirements for HelpDesk. The repository's `compose.yaml` is a **local development stack**, not a production deployment manifest: it bind-mounts the source tree, publishes the database and Mailpit ports, and uses development-oriented defaults. Do not expose that stack directly to the internet.

## 1. Choose and prepare the runtime

HelpDesk requires:

- PHP 8.4 with the extensions listed in the `Dockerfile`.
- A database supported by the configured Laravel connection (PostgreSQL is used by the supplied Docker stack).
- A web server or ingress that forwards PHP requests to PHP-FPM.
- Laravel Horizon running against a private Redis queue connection.
- Persistent storage for user uploads, plus a backup/restore process.
- Node.js 22 only for building frontend assets; Node is not needed by the PHP runtime.

Build the application image from a reviewed commit and deploy an immutable tag (prefer the full Git commit SHA). Do not deploy a mutable `:latest` tag.

## 2. Configure secrets and environment

Create environment variables in the hosting platform's secret manager. Do not commit a production `.env` file.

Required production settings include:

| Variable | Production guidance |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate a unique key once per environment and keep it stable across releases |
| `APP_URL` | Public HTTPS URL |
| `LOG_LEVEL` | Usually `warning` or `error`, adjusted to operational needs |
| `DB_CONNECTION` | `pgsql` when using PostgreSQL |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Private database endpoint and secret credentials |
| `QUEUE_CONNECTION` | `redis` (required by Laravel Horizon) |
| `SESSION_DRIVER`, `CACHE_STORE` | Choose stores appropriate to the deployment topology |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, credentials | Real transactional mail provider; never Mailpit |

Also set the demo account email addresses and password to non-demo values, or disable demo seeding entirely. Never run the demo seeder in a production environment.

Do not regenerate `APP_KEY` on every deployment: doing so invalidates encrypted data and sessions. Back it up securely and restrict access.

## 3. Build and release

A safe release pipeline should:

1. Run the repository CI and security scan; deploy only a commit that passed.
2. Build the Docker image, including Composer dependencies and Vite assets.
3. Scan the image and publish it to a private container registry.
4. Start a one-off release task using the exact image tag and run `php artisan migrate --force`.
5. Roll out the web application and queue worker using that same image.
6. Verify the health endpoint `/up`, authentication, database connectivity, and queue processing.
7. Keep the previous image available for rollback.

Run migrations as a single release task, not independently in every web or worker replica. Take a database backup before high-risk schema changes and use backward-compatible migrations when rolling releases.

## 4. Web and PHP-FPM

Configure the web server document root to the Laravel `public/` directory and forward PHP requests to PHP-FPM. Do not expose the project root, `.env`, source files, or storage internals as public static files.

Terminate TLS at a trusted load balancer or ingress and redirect HTTP to HTTPS. Configure trusted proxies and trusted hosts for the actual hosting platform. Set upload limits consistently in the ingress, web server, and PHP configuration.

The built frontend assets in `public/build` must be available to the web server. The current development Nginx service obtains application files through a bind mount; a production image/deployment must provide the public assets through its own immutable image or a deliberately shared artifact.

## 5. Queue worker and scheduler

Run a separate long-lived queue worker with the same release image and environment as the web application. The development Compose command is:

```bash
php artisan queue:work database --queue=notifications,default --sleep=3 --tries=3 --timeout=90
```

Use a process supervisor or the hosting platform to restart workers after failures and deployments. Gracefully restart workers during releases:

```bash
php artisan queue:restart
```

Monitor queue depth and failed jobs. Review failed jobs and retry only after the underlying issue has been understood:

```bash
php artisan queue:failed
php artisan queue:retry all
```

If scheduled Laravel tasks are introduced, run the scheduler using the platform's scheduler or a single cron entry:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

Do not run duplicate schedulers unless scheduled tasks are protected against concurrent execution.

## 6. Persistent data and backups

Persist the database independently of application containers. Persist uploaded files from the configured filesystem disk; container-local filesystems are not durable across replacement or rescheduling.

At minimum:

- Back up PostgreSQL on a defined schedule and encrypt backups.
- Back up uploaded files and any other persistent storage.
- Keep backups outside the primary host/account.
- Define retention and access policies.
- Test restoring both the database and uploaded files regularly.
- Document recovery objectives (RPO/RTO) and who can perform a restore.

A database backup alone is not sufficient if users' attachments are stored on a separate filesystem.

## 7. Network and operational security

- Keep PostgreSQL, PHP-FPM, queue backends, and administrative services on private networks.
- Do not publish the database port or Mailpit in production.
- Use least-privilege database and deployment credentials.
- Restrict outbound network access where practical.
- Keep base images and Composer/npm dependencies updated; review audit and Semgrep results.
- Configure centralized application, web server, database, and worker logs.
- Monitor HTTP error rates, latency, CPU/memory, disk space, database connections, queue depth, and failed jobs.
- Set up alerting and document incident and rollback procedures.

## 8. Health checks and release verification

Laravel exposes the framework health endpoint at:

```text
GET /up
```

Configure the platform's liveness/readiness checks against the deployed application and verify that the endpoint is reachable through the intended web path. A successful health response is not a substitute for monitoring database health, queue processing, or external mail delivery.

After each release, verify:

- The deployed image tag matches the intended commit.
- Migrations completed successfully.
- Login, ticket listing, ticket creation, and message submission work.
- Authorized attachment downloads work.
- A queued notification is processed.
- Logs contain no repeated exceptions or configuration errors.

## 9. Current deployment status

The repository currently has automated CI and Semgrep security scanning. The supplied Compose stack is intended for local development. A production deployment has **not** been configured or verified because the hosting provider, domain, registry, database service, and secret-management environment have not been selected.


## 8. Production Docker Compose and releases

The repository includes `compose.production.yaml`, which is separate from the local development/monitoring stack. It runs PHP-FPM, Nginx, the database, Redis and a queue worker. PostgreSQL is published only on 127.0.0.1 for host-side backup tooling; Redis remains private. Uploaded files use the persistent host directory configured by `HELPDESK_STORAGE_PATH` (default `./storage/app`). Configure a production `.env` on the host; never copy the development `.env.example` unchanged. Set `ELASTICSEARCH_ENABLED=false` unless a secured Elasticsearch endpoint is part of the production stack. Set `HELPDESK_STORAGE_PATH` to a durable host directory with sufficient disk space.

Release images are built from the `app` and `nginx` Dockerfile targets, scanned with Trivy, and published to GHCR under the immutable commit SHA. Create a version tag (for example `v1.0.0`) or run the Release Docker images workflow manually.

The manual Deploy production workflow requires these GitHub Environment secrets: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_PRIVATE_KEY`, `DEPLOY_KNOWN_HOSTS`, `GHCR_USERNAME`, and `GHCR_READ_TOKEN`. Set the environment variable `DEPLOY_PATH` to the existing application checkout on the server. The host must have Docker Compose, Git access to this repository, a valid production `.env`, and a reachable HTTP health endpoint.

The deployment script pulls the immutable image, runs migrations once, updates app/Nginx/worker containers, checks `/up`, and restores the previous application image tag if the health check fails. It does **not** reverse database migrations; migrations must remain backward-compatible with the previous release. Configure TLS and backups outside this Compose stack before exposing it publicly.
