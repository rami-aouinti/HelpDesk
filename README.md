# HelpDesk

A Laravel-based support desk for managing customer requests, team workflows and project delivery. The application combines a server-rendered web interface, Livewire interactions and a versioned REST API.

## Features

- **Authentication and accounts:** Laravel Fortify authentication, verified accounts, profile preferences and profile photos.
- **Role-based access:** administrator, agent and customer permissions powered by Spatie Laravel Permission.
- **Ticket management:** ticket lifecycle, priorities, assignment, teams, projects, filters, search and activity history.
- **Conversations:** public replies, staff-only internal notes and private attachments with authorization checks.
- **Project planning:** projects, team membership, budgets, time tracking, documents and sprints.
- **Kanban:** sprint-based board with role-aware ticket movement.
- **Notifications:** in-app notifications, email notifications and user notification preferences.
- **REST API:** Sanctum bearer tokens, ticket resources, conversation endpoints and OpenAPI documentation.

## Elasticsearch search

The optional `monitoring` Docker profile starts Elasticsearch and Kibana. HelpDesk indexes ticket documents asynchronously and can search tickets through Elasticsearch, with SQL fallback when Elasticsearch is unavailable. User documents are indexed asynchronously as users are created, updated, or deleted. Laravel jobs are processed by a dedicated Redis-backed queue worker.

Start Elasticsearch and Kibana:

```bash
docker compose --profile monitoring up -d elasticsearch kibana
```

Rebuild the user search index for existing accounts (the Redis queue worker processes the dispatched jobs):

```bash
docker compose exec app php artisan elasticsearch:reindex-users
```

The permission-protected API endpoint is:

```http
GET /api/v1/users/search?q=customer
Authorization: Bearer <sanctum-token>
```

Only users with the `users.view` permission can search accounts. The response contains basic account details and role names; passwords and authentication secrets are never indexed. Elasticsearch is configured through `ELASTICSEARCH_ENABLED` and `ELASTICSEARCH_URL` in `.env`.

## Requirements

- Docker Engine and Docker Compose plugin
- Git

The Docker image provides PHP 8.4-FPM and the required PHP extensions. Node.js 22 is used in the frontend build stage.

## Quick start (Docker)

```bash
git clone https://github.com/rami-aouinti/HelpDesk.git
cd HelpDesk

cp .env.example .env
docker compose config
docker compose up -d --build

docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Open:

- Application: http://localhost:8080
- Mailpit inbox: http://localhost:8025

For the full Docker monitoring stack (Grafana, Prometheus, Loki, Alloy, cAdvisor, exporters and alerting), see the [monitoring guide](docs/monitoring.md). Start it with `docker compose --profile monitoring up -d --build` after configuring the Grafana password in `.env`.

The demo accounts are configured through `HELPDESK_ADMIN_EMAIL`, `HELPDESK_AGENT_EMAIL`, `HELPDESK_CUSTOMER_EMAIL` and `HELPDESK_DEMO_PASSWORD` in `.env`. Change the demo password and credentials before sharing the environment or exposing it beyond your local machine. Never use the example database password or demo credentials in production.

## Useful commands

```bash
# Run all automated tests
docker compose exec app php artisan test

# Run database migrations
docker compose exec app php artisan migrate

# Seed demo data
docker compose exec app php artisan db:seed

# Inspect application and queue worker logs
docker compose logs -f app queue scheduler

# Run the PHP quality checks (development dependencies required)
docker compose exec app composer quality

# Rebuild after changing PHP dependencies, extensions or frontend dependencies
docker compose up -d --build

# Stop containers but preserve database data
docker compose down

# Delete containers and the local database volume (destructive)
docker compose down -v
```

## REST API v1

The API is served under `/api/v1`. Endpoints require an authenticated, active and email-verified user. Use a Sanctum personal access token:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

### Main endpoints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | `/api/v1/tokens` | Create a personal access token from an authenticated session |
| DELETE | `/api/v1/tokens/current` | Revoke the current bearer token |
| GET | `/api/v1/user` | Get the authenticated user |
| GET | `/api/v1/tickets` | List tickets visible to the current user |
| POST | `/api/v1/tickets` | Create a ticket |
| GET | `/api/v1/tickets/{ticket}` | Get ticket details and visible conversation |
| PATCH | `/api/v1/tickets/{ticket}` | Update ticket fields |
| POST | `/api/v1/tickets/{ticket}/messages` | Add a reply or permitted internal note |
| GET | `/api/v1/tickets/{ticket}/attachments/{attachment}` | Download an authorized attachment |

Ticket lists support pagination, status/priority filters, team/project/assignee/sprint filters, date ranges, text search and sorting. Customers only see their own tickets and public conversation content. Staff access is scoped by permissions and team/project visibility. Assignment fields and internal notes are protected by authorization rules.

### API documentation

- Interactive Swagger UI: [http://localhost:8080/api/documentation](http://localhost:8080/api/documentation)
- OpenAPI 3.1 document: [http://localhost:8080/api/openapi.yaml](http://localhost:8080/api/openapi.yaml)

## Architecture overview

- `app/Http`: web and API controllers, middleware and requests.
- `app/Livewire`: interactive ticket, team, project, sprint and Kanban interfaces.
- `app/Models`: Eloquent models and relationships.
- `app/Policies`: authorization rules.
- `app/Services`: ticket workflow and notification/application services.
- `app/Notifications`, `app/Events`, `app/Listeners`: asynchronous and event-driven behavior.
- `database/migrations`, `database/seeders`, `database/factories`: schema and demo/test data.
- `tests/Feature` and `tests/Unit`: automated regression coverage.

## Continuous integration

GitHub Actions runs on pushes and pull requests targeting `master`. The workflow validates Composer, installs dependencies, runs PHPCS, PHPStan, PHP Insights, Rector dry-run and Pint, builds frontend assets, then runs the Laravel test suite using in-memory SQLite.

## Monitoring and observability

The optional monitoring profile provides Grafana dashboards, Prometheus metrics and alert rules, Loki log aggregation, Alloy Docker log collection, cAdvisor, Node Exporter, PostgreSQL Exporter, Blackbox Exporter and Alertmanager. See [docs/monitoring.md](docs/monitoring.md) for setup, dashboards, alert delivery and security notes.

## Deployment

The supplied Docker Compose stack is for local development. See the [production deployment guide](docs/production-deployment.md) for environment configuration, release steps, queue workers, backups, health checks and security requirements. A production environment still needs to be configured and verified for its chosen hosting provider.

## Security notes

- Keep `.env` out of version control and generate a unique `APP_KEY` for every environment.
- Replace all example passwords and secrets before deployment.
- Serve production traffic over HTTPS and configure trusted hosts/proxies for the deployment platform.
- Use private object storage and backups appropriate to your deployment for uploaded attachments and database data.
- Do not expose the local Mailpit service or development database port publicly.
- Review dependencies and container base-image updates regularly.

## License

This project is distributed under the MIT license.
