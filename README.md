# HelpDesk

A hands-on Laravel learning project: build a realistic customer-support platform while learning Laravel from fundamentals to production practices.

The repository starts with a Docker development environment. The Laravel application source is generated into `src/` on the first startup, so you can run the project without installing PHP, Composer, Apache, or PostgreSQL on your host machine.

## Project goals

HelpDesk is a support-ticket platform where customers can open requests, support agents can manage and answer them, and administrators can configure the workspace and follow service activity.

We will learn Laravel progressively by implementing real features, understanding the framework conventions, writing tests, and reviewing each change before moving on.

## Technology choices

- **Backend:** Laravel (latest stable release available when the container is first built), PHP 8.4
- **Database:** PostgreSQL 17
- **Web server:** Apache with PHP-FPM-free mod_php setup
- **Local environment:** Docker Compose
- **Frontend learning tracks:** Blade, Livewire, Vue.js with Laravel APIs, and Inertia.js with Vue
- **Testing:** Pest or PHPUnit, selected and documented when we reach the testing phase

The four frontend approaches are intentional learning tracks. We will compare their strengths and implement them progressively rather than mixing all four into every page.

## Start the application

Requirements: Docker Desktop (or Docker Engine) and the Docker Compose plugin.

```bash
git clone https://github.com/rami-aouinti/HelpDesk.git
cd HelpDesk
docker compose up --build
```

On the first startup, Composer creates the Laravel application inside the mounted `src/` directory. The process can take a few minutes. Once Apache is ready, open:

**http://localhost:8080**

PostgreSQL is available to containers at `db:5432` and from the host at `localhost:5433`.

Useful commands:

```bash
# Follow application logs
docker compose logs -f app

# Open a shell in the PHP container
docker compose exec app bash

# Run Laravel Artisan commands
docker compose exec app php artisan about

# Run migrations when we introduce our first database schema
docker compose exec app php artisan migrate

# Stop containers (database data is preserved)
docker compose down

# Stop containers and delete the local database volume (destructive)
docker compose down -v
```

The Docker environment defaults are in `.env.docker.example`. Compose also accepts a local `.env` file; copy the example if you want to customize ports or database credentials.

## Documentation

- [Project definition and functional scope](docs/PROJECT.md)
- [Learning roadmap](docs/LEARNING-ROADMAP.md)
- [Docker development environment](docs/DOCKER.md)

## Development principles

1. Learn Laravel conventions before introducing custom abstractions.
2. Keep controllers focused on HTTP concerns.
3. Put business rules in appropriate application/domain services when complexity justifies it.
4. Use Eloquent relationships and query capabilities deliberately.
5. Validate input with Form Requests and authorize actions with Policies.
6. Cover important behaviour with automated tests.
7. Prefer simple, maintainable solutions over patterns added only for the sake of architecture.

## Current status

**Phase 0 – Project definition and local environment.** No business features have been implemented yet. The first learning session begins only after the environment is running and verified.
