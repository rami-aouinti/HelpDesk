# Docker Development Environment

## Services

| Service | Image/build | Purpose | Host access |
|---|---|---|---|
| app | Custom PHP 8.4 + Apache | Laravel application | http://localhost:8080 |
| db | PostgreSQL 17 Alpine | Relational database | localhost:5433 |

The app container connects to PostgreSQL using hostname `db` and port `5432`. The host port `5433` is only for optional local database clients.

## First startup

```bash
docker compose up --build
```

The application container mounts `./src` into `/var/www/html`. If the Laravel `artisan` file is absent, the entrypoint runs Composer to create a fresh Laravel application in that directory, configures its local database connection, generates an application key, and starts Apache.

The first build and Composer install require an internet connection and may take several minutes.

Open http://localhost:8080 after the app container has started.

## Everyday commands

```bash
docker compose up -d
docker compose ps
docker compose logs -f app
docker compose logs -f db
docker compose exec app bash
docker compose exec app php artisan list
docker compose down
```

## Database access

- From the Laravel container: host `db`, port `5432`.
- From a database client on your computer: host `127.0.0.1`, port `5433`.
- Default database: `helpdesk`.
- Default username: `helpdesk`.
- Default password: `secret`.

These are development-only defaults. Do not reuse them in production.

## Configuration

Copy `.env.docker.example` to `.env` at the repository root to override Compose variables:

```bash
cp .env.docker.example .env
```

For example, set `APP_PORT=8081` if port 8080 is already occupied. Recreate the containers after changing Compose environment values:

```bash
docker compose up -d --force-recreate
```

The Laravel environment file is generated at `src/.env` inside the bind-mounted source directory. It is ignored by Git.

## Common issues

### Port 8080 is already in use
Set `APP_PORT=8081` in the root `.env` file, recreate the app, and use http://localhost:8081.

### Port 5433 is already in use
Set `DB_FORWARD_PORT=5434`. The app still connects internally to `db:5432`.

### Laravel source was only partially generated
Inspect logs with `docker compose logs app`. If Composer was interrupted, inspect `src/`; remove the incomplete generated source only if it is disposable, then run `docker compose up --build` again.

### Database credentials changed after the volume was initialized
PostgreSQL only applies its initialization variables when the data directory is empty. For a disposable learning database, `docker compose down -v` removes the volume and data; then start again. **This permanently deletes the local database contents.**

### Laravel cannot write cache or log files
The entrypoint sets ownership of `storage` and `bootstrap/cache` to `www-data`. If host filesystem permissions prevent writes, inspect the mounted directory permissions before changing ownership or permissions.

## Data persistence

PostgreSQL data is stored in the named Docker volume `helpdesk-postgres-data`. Normal `docker compose down` preserves it. Only `docker compose down -v` deletes it.
