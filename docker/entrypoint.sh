#!/bin/sh
set -eu

if [ ! -f artisan ]; then
    echo "Laravel source not found. Creating a fresh Laravel application..."
    composer create-project laravel/laravel . --no-interaction
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

set_env() {
    key="$1"
    value="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

set_env APP_ENV "${APP_ENV:-local}"
set_env APP_DEBUG "${APP_DEBUG:-true}"
set_env APP_URL "${APP_URL:-http://localhost:8080}"
set_env DB_CONNECTION pgsql
set_env DB_HOST "${DB_HOST:-db}"
set_env DB_PORT "${DB_PORT:-5432}"
set_env DB_DATABASE "${DB_DATABASE:-helpdesk}"
set_env DB_USERNAME "${DB_USERNAME:-helpdesk}"
set_env DB_PASSWORD "${DB_PASSWORD:-secret}"

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
