# HelpDesk monitoring and observability

The optional Docker monitoring profile provides infrastructure metrics, HTTP availability checks, centralized container logs, dashboards and local alert delivery.

## Stack

| Component | Purpose |
| --- | --- |
| Prometheus | Scrapes metrics every 15 seconds and evaluates alert rules |
| Grafana | Unified dashboards for metrics and logs |
| Loki | Stores and queries Docker container logs (7-day retention) |
| Grafana Alloy | Discovers Docker containers and ships their logs to Loki |
| cAdvisor | Container CPU, memory, filesystem and network metrics |
| Node Exporter | Host/kernel metrics visible from the Docker engine |
| PostgreSQL Exporter | PostgreSQL health, activity and failed Laravel queue job count |
| Redis Exporter | Dedicated Redis queue availability, memory and keyspace metrics |
| Blackbox Exporter | Probes the application health endpoint and API documentation over HTTP |
| Alertmanager | Groups Prometheus alerts and emails them to the local Mailpit inbox |

## Start

Copy the environment file if this is a fresh checkout:

```bash
cp .env.example .env
```

Before starting the monitoring profile, change `GRAFANA_ADMIN_PASSWORD` in `.env` to a strong local password. Then start the full application and observability stack:

```bash
docker compose --profile monitoring up -d --build
```

If the application was already running, the same command adds the monitoring services without deleting the database volume.

## Local URLs

| Service | URL |
| --- | --- |
| HelpDesk | http://localhost:8080 |
| Grafana dashboard | http://localhost:3001 |
| Prometheus | http://localhost:9090 |
| Alertmanager | http://localhost:9093 |
| Mailpit alert inbox | http://localhost:8025 |

Grafana uses the username and password from `.env` (`GRAFANA_ADMIN_USER` and `GRAFANA_ADMIN_PASSWORD`). Prometheus and Alertmanager are bound to localhost. Loki and the exporters are only reachable on the internal Docker networks.

The Grafana dashboard **HelpDesk - Infrastructure & Availability** is provisioned automatically. Prometheus and Loki datasources are also provisioned automatically.

## What is monitored

- Application availability: `/up` and `/api/documentation`, response success and response duration.
- Container resources: CPU, memory, filesystem and network through cAdvisor.
- Docker host/kernel metrics through Node Exporter.
- PostgreSQL exporter availability, connections, database statistics and failed job count.
- Dedicated Redis queue availability, memory and keyspace metrics through Redis Exporter.
- Queue backlog threshold checks every minute via Laravel's `queue:monitor` for `redis:default` and `redis:notifications`; threshold breaches are emitted as structured `queue.backlog.threshold_exceeded` logs for Grafana/Loki. Pending job counts are not read from PostgreSQL because jobs are stored in Redis.
- Horizon's **Monitoring** tab tracks tagged jobs, not worker supervisors. HelpDesk registers `ticket-notifications`, `ticket-search-indexing` and `user-search-indexing` automatically through `horizon:monitor-tags`; the scheduled command re-registers them after the Horizon Redis data is recreated. These tags appear in the Monitoring tab when matching jobs have run.
- Container stdout/stderr logs through Docker discovery, Alloy and Loki.

Laravel's example environment sets `LOG_STACK=stderr`, so Laravel logs are emitted to the container output and collected by Alloy. Keep this setting if you want application logs in Grafana Explore.

## Horizon dashboard monitoring

The Horizon dashboard is available at `http://localhost:8080/horizon`. Its **Monitoring** tab tracks individual job tags. HelpDesk tags the asynchronous Elasticsearch indexing jobs with `ticket-search-indexing` and `user-search-indexing`, then registers those tags automatically:

```bash
docker compose exec app php artisan horizon:monitor-tags
```

The scheduler also runs this command every minute, so the monitored-tag configuration is restored after the Horizon Redis data is recreated. The **Metrics** tab is populated by the scheduled `horizon:snapshot` command every five minutes. The supervisor and worker configuration remains in `config/horizon.php`.

## Alerts

Prometheus evaluates rules in `docker/monitoring/prometheus/alerts.yml`. Current rules cover:

- HTTP endpoint unavailable for 2 minutes.
- HTTP response duration above 2 seconds for 5 minutes.
- PostgreSQL exporter unavailable.
- Container CPU above 85% for 10 minutes.
- Container memory above 90% of its configured limit for 10 minutes.
- Redis queue exporter or Redis queue unavailable.
- One or more failed Laravel queue jobs.

Alertmanager sends alert emails to `admin@helpdesk.test` through Mailpit. This is deliberately local-only: open Mailpit at http://localhost:8025 to inspect alert notifications. No external email is sent. For a real deployment, replace the Mailpit SMTP target with an authenticated SMTP relay and protect its credentials as deployment secrets.

## Useful commands

```bash
# Check all services, including the monitoring profile
docker compose --profile monitoring ps

# Follow monitoring service logs
docker compose logs -f prometheus grafana loki alloy alertmanager postgres-exporter redis-queue-exporter blackbox-exporter

# Check readiness endpoints
curl -fsS http://localhost:9090/-/ready
curl -fsS http://localhost:3001/api/health
curl -fsS http://localhost:9093/-/ready

# Inspect queue failures from Laravel
docker compose exec app php artisan queue:failed

# Stop all services but keep persistent data
docker compose --profile monitoring down
```

To run the application without observability services, use the normal `docker compose up -d --build` command without the `monitoring` profile.

## Operational and security notes

- Prometheus retains 15 days of metrics; Loki retains 7 days of logs. Adjust these values and provision adequate disk space for longer retention.
- Grafana, Prometheus and Alertmanager publish their host ports on `127.0.0.1`, not on every network interface. Use an authenticated VPN or a protected reverse proxy if remote access is required.
- Alloy needs access to the Docker API socket to discover and read container logs. Treat the Alloy container as a trusted infrastructure component; read-only mounting of the socket does not make Docker API operations harmless.
- cAdvisor needs elevated access to inspect container metrics. Review this permission before adapting the profile to a hardened production host.
- On Docker Desktop for Windows/macOS, Node Exporter reports the Linux VM/environment backing Docker rather than all physical host operating-system metrics. Container metrics remain available through cAdvisor.
- This Compose setup is a single-node development/operations stack, not a highly available production monitoring platform. Production deployments should use managed/secured monitoring, external durable storage, authentication, TLS, backups and tested notification channels.
