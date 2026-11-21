# Laravel Learning Roadmap

We move phase by phase. Each phase introduces a concept, applies it to HelpDesk, includes a small exercise, and ends with a review and tests where appropriate.

## Phase 0 — Project setup
- Understand the repository and Docker Compose services.
- Start the application and inspect Laravel's directory structure.
- Learn the request lifecycle at a high level.
- Verify the PHP extensions and PostgreSQL connection.

**Exit criteria:** Laravel welcome page opens at http://localhost:8080 and Artisan commands run in the container.

## Phase 1 — Laravel fundamentals
- Artisan and project structure.
- Routes, route parameters, named routes, route groups.
- Controllers, requests, and responses.
- Blade templates, layouts, components.
- Configuration, environment variables, logging.
- Middleware and CSRF.

**HelpDesk outcome:** a simple public landing page and a first server-rendered ticket-information page using sample data.

## Phase 2 — Database and Eloquent
- PostgreSQL connection and configuration.
- Migrations, schema builder, seeders, factories.
- Models, mass assignment, casts.
- Eloquent CRUD and query builder.
- One-to-one, one-to-many, and many-to-many relationships.
- Eager loading, pagination, transactions, indexes.

**HelpDesk outcome:** users, teams, tickets, and messages persisted in PostgreSQL.

## Phase 3 — Authentication and authorization
- Laravel authentication options and session lifecycle.
- Registration, login, logout, password reset.
- Middleware, gates, policies.
- Role checks and ownership rules.
- Authorization tests.

**HelpDesk outcome:** customer, agent, and administrator access with ticket privacy enforced.

## Phase 4 — Validation and application design
- Form Requests and validation rules.
- Dependency injection and the service container.
- DTOs when they clarify boundaries.
- Actions/services for meaningful use cases.
- Enums, value objects, and domain events where useful.
- Repository pattern only when it solves a concrete problem.

**HelpDesk outcome:** clean ticket creation, assignment, and status-transition use cases.

## Phase 5 — Ticket workflows
- Status transition rules.
- Assignment and team routing.
- Public replies versus private notes.
- Database transactions and concurrency considerations.
- Activity history and auditability.

**HelpDesk outcome:** a complete ticket lifecycle with tested business rules.

## Phase 6 — Events and asynchronous work
- Events and listeners.
- Jobs, queues, retries, failed jobs.
- Mail and notifications.
- Redis as a cache/queue option.
- Task scheduling.

**HelpDesk outcome:** queued email notifications for new tickets, assignments, and replies.

## Phase 7 — REST API
- API routes and versioning.
- Form Request validation and API Resources.
- Authentication with Sanctum.
- Pagination, filtering, sorting, error formats.
- API feature tests and API documentation.

**HelpDesk outcome:** a documented API for ticket operations.

## Phase 8 — Testing and quality
- PHPUnit and Pest comparison.
- Unit versus feature tests.
- Database refresh and factories.
- HTTP tests, authorization tests, mocking and fakes.
- Static analysis, formatting, and CI.

**HelpDesk outcome:** reliable automated coverage for critical workflows.

## Phase 9 — Frontend comparison
- Blade: server-rendered pages and components.
- Livewire: interactive PHP components.
- Vue: independent frontend consuming the REST API.
- Inertia + Vue: SPA-like navigation with Laravel controllers.
- Compare developer experience, complexity, validation, state, and deployment.

**HelpDesk outcome:** representative ticket workflows implemented and compared in all four approaches.

## Phase 10 — Production readiness
- Production Docker image and non-root runtime.
- Environment/secrets management.
- Apache/PHP and database tuning basics.
- Logging, health checks, backups.
- CI/CD and deployment.
- Security review and performance profiling.

**HelpDesk outcome:** documented deployment process and production-readiness checklist.

## Learning session format

For each topic:
1. Explain the Laravel concept and its Symfony equivalent only when comparison helps.
2. Inspect a small example.
3. Implement one focused HelpDesk requirement.
4. Run it locally and inspect the result.
5. Add or run tests.
6. Review the design and summarize what was learned.

Do not skip ahead just to add infrastructure. Understand the feature before adding abstractions.
