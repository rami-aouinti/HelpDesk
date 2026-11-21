<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>


## Run locally with Docker

The repository includes a Docker Compose development environment with PHP 8.4, Laravel, Nginx, PostgreSQL 17, a database queue worker, and Mailpit.

### First startup

```bash
cp .env.example .env
docker compose config
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link
```

Open the application at [http://localhost:8080](http://localhost:8080) and Mailpit at [http://localhost:8025](http://localhost:8025).

The demo credentials are configured by `HELPDESK_ADMIN_EMAIL`, `HELPDESK_AGENT_EMAIL`, `HELPDESK_CUSTOMER_EMAIL`, and `HELPDESK_DEMO_PASSWORD` in your local `.env` file. Change these values before exposing the application outside your development machine.

### Useful commands

```bash
# Run the test suite (PHPUnit uses in-memory SQLite)
docker compose exec app php artisan test

# Run migrations after pulling new changes
docker compose exec app php artisan migrate

# Follow application and queue-worker logs
docker compose logs -f app queue

# Stop the stack (database data is preserved)
docker compose down

# Stop the stack and delete the local PostgreSQL volume
docker compose down -v
```

The application source is bind-mounted into the containers, so PHP changes are available immediately. Rebuild the image after changing PHP extensions, Composer dependencies, or frontend dependencies/assets:

```bash
docker compose up -d --build
```


## REST API v1

All v1 endpoints require an authenticated, active, email-verified user. Authenticate with a Sanctum personal access token:

```http
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

### Sanctum

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | `/api/v1/tokens` | Create a personal access token (requires an authenticated session) |
| DELETE | `/api/v1/tokens/current` | Revoke the current bearer token |
| GET | `/api/v1/user` | Return the authenticated user |

The original `/api/tokens` and `/api/user` endpoints remain available for backwards compatibility.

### Tickets

| Method | Endpoint | Permission |
| --- | --- | --- |
| GET | `/api/v1/tickets` | `tickets.view` |
| POST | `/api/v1/tickets` | `tickets.create` |
| GET | `/api/v1/tickets/{ticket}` | Ticket policy: `view` |
| PATCH | `/api/v1/tickets/{ticket}` | Ticket policy: `update` |

The list is automatically scoped to the authenticated user's visibility: customers see their own tickets, agents see tickets belonging to their teams and projects, and administrators with `tickets.view.all` can see all tickets. Customer ticket resources never expose internal notes. Assignment fields remain administrator-only, and status transitions are enforced by the same domain service used by the web application.

Ticket lists are paginated (15 per page by default). Use `?page=2&per_page=30`; `per_page` is capped at 100.

Supported list filters:

| Query parameter | Description |
| --- | --- |
| `status`, `priority` | Filter by ticket status or priority |
| `team_id`, `project_id`, `assigned_to`, `sprint_id` | Filter by team, project, assigned agent, or sprint |
| `unassigned=true/false` | Filter unassigned or assigned tickets |
| `search` | Search subject, description, or numeric ticket ID |
| `created_from`, `created_to` | Filter by creation date |
| `updated_from`, `updated_to` | Filter by last update date |
| `sort_by` | `created_at`, `updated_at`, `priority`, `status`, or `subject` |
| `sort_direction` | `asc` or `desc` |

### Conversations and attachments

| Method | Endpoint | Purpose |
| --- | --- | --- |
| POST | `/api/v1/tickets/{ticket}/messages` | Add a reply or staff-only internal note; supports multipart attachments |
| GET | `/api/v1/tickets/{ticket}/attachments/{attachment}` | Securely download an attachment |

Customers can only post public replies and only see/download public conversation content. Staff with update permission may add internal notes. Uploads are limited to five files per message, 10 MB per file, and an allowlist of document/image formats. Files are stored on the private local disk; storage paths are never exposed in API resources.

The message and attachment resources are nested in the ticket detail response. Download links are generated through the authenticated API route.

### Sprint planning and Kanban

The web application includes Sprint Management and a Livewire Kanban board:

- Sprints have a name, goal, start date and end date. A ticket belongs to at most one sprint.
- A sprint is automatically considered completed when it contains at least one ticket and every ticket has status `closed` (the workflow's final `done` state).
- Administrators can create, edit and delete empty sprints, and move any ticket.
- Agents can move only tickets assigned to themselves, and status changes still respect the ticket workflow.
- The Kanban board opens the latest sprint by default and offers an "Only my assigned tickets" filter to agents and administrators.
- A sprint containing tickets cannot be deleted; move its tickets to another sprint first.

Pages: [Sprints](/sprints) and [Kanban](/kanban).

### Interactive documentation

Open [Swagger UI](/api/documentation) to explore and try the API. The OpenAPI 3.1 document is available at [openapi.yaml](/api/openapi.yaml).

Example create request:

```http
POST /api/v1/tickets
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN

{
  "subject": "Cannot access my account",
  "description": "The password reset link is not working for me.",
  "priority": "normal"
}
```

Successful creation returns HTTP 201 and a JSON ticket resource. Validation failures return HTTP 422 with Laravel's standard JSON `message` and `errors` structure; unauthorized requests return HTTP 401 and policy denials return HTTP 403.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
