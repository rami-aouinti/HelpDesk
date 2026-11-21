# HelpDesk Elasticsearch strategy

Elasticsearch has two explicit responsibilities:

1. **Operational telemetry**: Filebeat indexes container output in `helpdesk-logs-YYYY.MM.DD`. Laravel emits structured `http.request`, `http.request.exception`, and queue lifecycle events.
2. **Ticket search**: the queued `IndexTicketInElasticsearch` listener maintains one document per ticket in the `helpdesk-tickets` index. The document contains ticket text, status, priority and relation IDs only. It deliberately excludes customer names, email addresses, message bodies, attachments, tokens and other personal data.

## Ticket indexing

Set `ELASTICSEARCH_URL=http://elasticsearch:9200`. The local Compose Elasticsearch service is connected to the application network. Ticket create/update events enqueue indexing work; failures are retried by Laravel's queue worker. The SQL database remains the source of truth. Elasticsearch is a rebuildable search projection, not a transactional database.

To inspect the projection in Kibana Dev Tools:

```http
GET _cat/indices/helpdesk-*?v
GET helpdesk-tickets/_count
GET helpdesk-tickets/_search
```

A full rebuild can be performed by replaying the ticket indexing listener for each ticket from a one-off Artisan command; never edit Elasticsearch documents as the source of truth.

## Kibana

Create a data view named `helpdesk-logs-*` with `@timestamp` as the time field, and another named `helpdesk-tickets`. Useful saved searches:
- HTTP traffic: request count by method/path/status and latency.
- Workers: started/completed/failed jobs by queue and job class.
- Ticket search: subject and description with status/priority filters.

Filebeat ships Docker container logs to Elasticsearch. Keep retention bounded in production with an ILM policy and enable Elasticsearch authentication/TLS before exposing it outside localhost.

## Cache

Redis is the Laravel cache store. Use a five-minute TTL for suitable read-through queries. Never cache authorization decisions or user-specific data under shared keys.


## Import the Kibana operations dashboard

Open Kibana at `http://localhost:5601`, go to **Stack Management → Saved Objects → Import**, and select `docker/monitoring/kibana/helpdesk-operations.ndjson`. The import creates the `helpdesk-logs-*` data view plus saved searches and an operations dashboard for HTTP and worker events.
