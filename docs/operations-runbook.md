# HelpDesk operations runbook

## Load smoke test

Install k6, start the local stack, then run:

```bash
k6 run k6/helpdesk-smoke.js
BASE_URL=http://localhost:8080 API_TOKEN='<sanctum-token>' VUS=10 DURATION=2m k6 run k6/helpdesk-smoke.js
```

The smoke test checks the health endpoint and, when an API token is supplied, the authenticated ticket list. It uses conservative thresholds; establish production-specific baselines before treating them as capacity targets.

## PostgreSQL and uploads backup

Run from the project root with database environment variables available:

```bash
DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=helpdesk DB_USERNAME=helpdesk DB_PASSWORD='<secret>' UPLOADS_DIR=./storage/app ./scripts/backup.sh
```

The script creates a custom-format PostgreSQL dump, archives `storage/app/public`, writes SHA-256 checksums and removes backups older than the configured retention period. Store backups encrypted and off-host; local files alone are not disaster recovery.

## Restore drill

Restore is destructive and requires typing `RESTORE`:

```bash
DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=helpdesk DB_USERNAME=helpdesk DB_PASSWORD='<secret>' UPLOADS_DIR=./storage/app ./scripts/restore.sh backups/helpdesk-db-<timestamp>.dump backups/helpdesk-uploads-<timestamp>.tar.gz
```

Perform restore drills against an isolated database and directory first. A successful dump is not proof of recoverability.

## Access and concurrency

The existing feature tests cover customer ownership, team isolation, project membership, and authorization boundaries. Keep those tests in CI. Ticket claiming uses a database transaction and row lock; run concurrent claim tests against PostgreSQL (SQLite in-memory does not prove PostgreSQL lock behavior).

## Elasticsearch checks

In Kibana Dev Tools:

```http
GET _cat/indices/helpdesk-*?v
GET helpdesk-tickets/_count
GET helpdesk-logs-*/_search
```

If ticket indexing is delayed, inspect Laravel failed jobs and queue logs. PostgreSQL remains authoritative; reindex rather than editing Elasticsearch documents manually.
