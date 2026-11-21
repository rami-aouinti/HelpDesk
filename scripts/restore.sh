#!/usr/bin/env bash
set -Eeuo pipefail

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 <database.dump> [uploads.tar.gz]" >&2
  exit 2
fi

database_file="$1"
uploads_file="${2:-}"
: "${DB_DATABASE:?DB_DATABASE is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"
: "${DB_HOST:=localhost}"
: "${DB_PORT:=5432}"
: "${UPLOADS_DIR:=./storage/app}"

[[ -f "$database_file" ]] || { echo "Database backup not found: $database_file" >&2; exit 2; }
if [[ -n "$uploads_file" && ! -f "$uploads_file" ]]; then
  echo "Uploads backup not found: $uploads_file" >&2
  exit 2
fi

echo "WARNING: this replaces objects in database '$DB_DATABASE'."
read -r -p "Type RESTORE to continue: " confirmation
[[ "$confirmation" == "RESTORE" ]] || { echo "Restore cancelled."; exit 1; }

export PGPASSWORD="${DB_PASSWORD:?DB_PASSWORD is required}"
pg_restore --clean --if-exists --no-owner --exit-on-error --host="$DB_HOST" --port="$DB_PORT" --username="$DB_USERNAME" --dbname="$DB_DATABASE" "$database_file"
unset PGPASSWORD

if [[ -n "$uploads_file" ]]; then
  mkdir -p "$UPLOADS_DIR"
  tar -xzf "$uploads_file" -C "$UPLOADS_DIR"
fi

echo "Restore completed. Verify the application and uploaded files before reopening traffic."
