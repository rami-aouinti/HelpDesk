# HelpDesk

HelpDesk is a Laravel 13 support-ticket application built with PHP 8.4, PostgreSQL 17, Docker Compose and the official Laravel Livewire starter-kit stack.

## Stack

- Laravel 13 / PHP 8.4
- Livewire 4, Tailwind CSS 4 and Flux UI
- Laravel Fortify for registration, login, password reset and email verification
- Spatie Laravel Permission for the admin, agent and customer roles
- Laravel Sanctum for personal API tokens
- PostgreSQL 17
- Mailpit for local SMTP and email inspection
- PHPUnit feature tests

## Start locally

Requirements: Docker Desktop (or Docker Engine) and Docker Compose.

```bash
git clone https://github.com/rami-aouinti/HelpDesk.git
cd HelpDesk
docker compose up --build -d
```

Install the PHP and frontend dependencies in the application container. The Composer manifest has changed as part of the Livewire/authentication integration, so run `composer update` once to refresh the lock file; keep the generated `src/composer.lock` in Git afterwards.

```bash
docker compose exec app composer update --no-interaction
docker compose exec app npm install
docker compose exec app npm run build
docker compose exec app php artisan migrate --seed
```

Open:

- HelpDesk: http://localhost:8080
- Adminer: http://localhost:8081
- Mailpit inbox: http://localhost:8025

Mailpit accepts SMTP connections from Laravel at `mailpit:1025`. Verification and password-reset emails are captured locally; no real email is sent.

## Development users

The database seeder creates three local demo users:

| Role | Email |
|---|---|
| Administrator | `admin@helpdesk.test` |
| Support agent | `agent@helpdesk.test` |
| Customer | `customer@helpdesk.test` |

The default local demo password is `password`. Override it with `HELPDESK_DEMO_PASSWORD` before running the seeder. Do not use the demo password outside local development.

## Useful commands

```bash
# Run all migrations and seed the demo accounts
docker compose exec app php artisan migrate --seed

# Run the feature and unit tests
docker compose exec app php artisan test

# Inspect routes
docker compose exec app php artisan route:list

# Clear Laravel caches
docker compose exec app php artisan optimize:clear

# Follow application logs
docker compose logs -f app

# Stop containers; PostgreSQL data is preserved
docker compose down
```

Do not run `docker compose down -v` unless you intentionally want to delete the PostgreSQL volume and all local HelpDesk data.

## Access model

- Customers can create tickets and see only their own tickets.
- Agents can view and update tickets.
- Administrators receive all ticket permissions.
- Ticket status and priority changes are stored in `ticket_histories`.
- API clients authenticate with Sanctum bearer tokens.

## API

All API endpoints are prefixed with `/api`.

- `GET /api/user` — current authenticated user
- `POST /api/tokens` — create a personal access token (`name` required)
- `DELETE /api/tokens/current` — revoke the current token

These endpoints require Sanctum authentication. Use an existing session or bearer token to create a token.

## Project structure

Laravel source code lives in `src/`. Docker configuration lives in `docker/` and `docker-compose.yml`. Business features remain in controllers, Form Requests, policies, services, models and Livewire components.
