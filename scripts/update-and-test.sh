#!/usr/bin/env bash
set -Eeuo pipefail

# Always run commands from the repository root.
ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "==> Pulling latest changes from origin/master"
git pull origin master

echo "==> Clearing Laravel optimized caches"
docker compose exec app php artisan optimize:clear

echo "==> Running the test suite"
docker compose exec app php artisan test

echo "==> Running database migrations and seeders"
docker compose exec app php artisan migrate --seed

echo "==> Update, tests and database setup completed successfully."
