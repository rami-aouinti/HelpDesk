#!/usr/bin/env bash
set -Eeuo pipefail

image_tag="${1:?Usage: deploy-production.sh <immutable-image-tag>}"
repo_image="ghcr.io/rami-aouinti/helpdesk"
compose=(docker compose --env-file .env -f compose.production.yaml)
state_file=".last-good-image-tag"

[[ -f .env ]] || { echo "Missing production .env file." >&2; exit 1; }
storage_path="$(sed -n 's/^HELPDESK_STORAGE_PATH=//p' .env | tail -n 1)"
storage_path="${storage_path:-./storage/app}"
mkdir -p "$storage_path/private" "$storage_path/public"
chown -R 33:33 "$storage_path"
[[ "$image_tag" =~ ^[A-Za-z0-9_.-]+$ ]] || { echo "Invalid image tag." >&2; exit 2; }

previous_tag=""
if [[ -f "$state_file" ]]; then previous_tag="$(cat "$state_file")"; fi

export HELPDESK_IMAGE="$repo_image:$image_tag"
export HELPDESK_NGINX_IMAGE="$repo_image-nginx:$image_tag"

"${compose[@]}" config --quiet
"${compose[@]}" pull
"${compose[@]}" run --rm --no-deps app php artisan migrate --force
"${compose[@]}" up -d --remove-orphans

app_port="$(sed -n 's/^APP_PORT=//p' .env | tail -n 1)"
app_port="${app_port:-80}"
health_url="${APP_HEALTH_URL:-http://127.0.0.1:$app_port/up}"
healthy=false
for attempt in $(seq 1 30); do
  if curl --fail --silent --show-error "$health_url" >/dev/null 2>&1; then
    healthy=true
    break
  fi
  sleep 5
done

if [[ "$healthy" != true ]]; then
  echo "New release did not become healthy: $health_url" >&2
  if [[ -n "$previous_tag" ]]; then
    echo "Rolling application containers back to $previous_tag (database migrations are not reversed)."
    export HELPDESK_IMAGE="$repo_image:$previous_tag"
    export HELPDESK_NGINX_IMAGE="$repo_image-nginx:$previous_tag"
    "${compose[@]}" pull
    "${compose[@]}" up -d --remove-orphans
  else
    echo "No previous release tag is recorded; manual recovery is required." >&2
  fi
  exit 1
fi

printf '%s\n' "$image_tag" > "$state_file"
"${compose[@]}" exec -T app php artisan queue:restart
echo "Deployment $image_tag is healthy."
