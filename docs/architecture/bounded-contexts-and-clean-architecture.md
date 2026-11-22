# HelpDesk Architecture: Bounded Contexts, Clean Architecture and CQRS

## Architecture decision
HelpDesk remains a modular monolith. Business capabilities are separated into bounded contexts; each context owns its application use cases and domain model. Laravel is the delivery/runtime framework and an infrastructure detail, not the domain model.

The architecture uses four layers inside each context:
- **Domain**: business rules, entities/aggregates, value objects, domain events and repository contracts. Must not depend on Laravel, Eloquent, HTTP, queues or external services.
- **Application**: use cases. Commands change state; queries read state. Handlers orchestrate domain behavior through contracts.
- **Infrastructure**: Eloquent mappings, database repositories, queue/event adapters, external integrations and framework bindings.
- **Presentation**: HTTP controllers, Form Requests, API Resources, Livewire/UI and console entry points. It translates input/output and delegates to application use cases.

Dependency direction: Presentation -> Application -> Domain. Infrastructure implements contracts defined inward and is wired at the application boundary. Domain never depends on outer layers.

## Bounded contexts

### 1. IdentityAccess
Owns user identity and access decisions: users, account lifecycle/profile, authentication-facing concerns, roles, permissions, authorization policies and team membership/access grants.
Do not put ticket assignment rules here. IdentityAccess answers who a user is and what they may access; ServiceDesk decides whether an assignment is valid in its workflow.

### 2. ServiceDesk
The core business context: tickets, lifecycle, status transitions, priorities, assignment/claiming workflow, conversations, internal notes, attachments, SLA policies/deadlines/escalations, ticket history and domain events.
This context owns ticket invariants. It must not expose Eloquent models as its domain entities.

### 3. WorkManagement
Owns projects, project membership, sprints, work planning, calendar events and scheduling.
A ticket may reference a project or sprint through identifiers/contracts; avoid coupling the ServiceDesk domain to WorkManagement Eloquent models.

### 4. Insights
Owns management reports, KPI definitions, dashboard projections/aggregates and CSV/PDF export use cases.
Insights may read data from several contexts through dedicated read models/projection tables or query contracts. It must not become the owner of ticket or user business rules.

### 5. Communications
Owns notification preferences/templates, email and in-app delivery orchestration, delivery channels, retries and delivery status.
It reacts to integration/domain events and should not own ticket lifecycle decisions.

### 6. Platform
Owns cross-cutting technical capabilities: audit infrastructure where not part of a business aggregate, search indexing adapters, application configuration, feature flags, observability, health checks and integration adapters.
Platform is not a dumping ground for business features. Business-specific rules remain in their owning context.

## CQRS conventions
- Commands express intent and change state: CreateTicketCommand, ClaimTicketCommand, ResolveTicketCommand.
- Queries retrieve data and do not mutate state: GetTicketQuery, SearchTicketsQuery.
- Each command/query has a handler. Start with direct handler invocation through Laravel's container; do not introduce a message-bus package until there is a concrete need.
- Write repositories protect aggregate consistency. Query handlers may use optimized Eloquent/SQL read models without loading full aggregates.
- Domain events describe facts that have happened. Infrastructure/application adapters translate them to Laravel events or queued integration events.
- Do not introduce event sourcing. Persist current state normally; use events for side effects and projections.

## Initial implementation sequence
1. Establish the context boundaries and dependency rules documented here.
2. Migrate ServiceDesk ticket creation as the first vertical slice: pure domain aggregate, command, handler, repository contract, Eloquent adapter and framework binding.
3. Preserve current notifications, Elasticsearch indexing and API cache invalidation through adapters/listeners; do not silently remove side effects.
4. Add ticket queries separately from write use cases.
5. Migrate ticket claiming through `ClaimTicketCommand`: the aggregate enforces claim invariants, the handler locks and persists the ticket transactionally, and adapters preserve history, notifications, Elasticsearch indexing and API cache invalidation.
6. Migrate administrative agent assignment through `AssignTicketToAgentCommand`, including active-agent/team membership checks, reassignment history and the existing update-event side effects.
7. Migrate status changes through `ChangeTicketStatusCommand` and its domain transition graph. The HTTP controller delegates status-only updates to the command handler; assignment-only updates are handled by `AssignTicketToAgentCommand`.
8. Migrate priority-only changes through `ChangeTicketPriorityCommand`; the handler locks and saves the aggregate, records history and publishes the existing update side effects.
9. Migrate team-only assignment through `AssignTicketToTeamCommand`; preserve the assigned-agent/team membership invariant and record the change in ticket history.
10. Migrate project linking through `LinkTicketToProjectCommand`; enforce project membership (or `projects.view.all`), support unlinking, and record the change in ticket history.
11. Migrate agent unassignment through `UnassignTicketFromAgentCommand`; keep the team association, record the old/new agent in history, and publish the existing update side effects. Only administrators may unassign.
12. Migrate effort tracking through `UpdateTicketEffortCommand`; keep estimated and elapsed minutes in the Ticket aggregate, preserve omitted values in partial updates, and record each changed field in history.
13. Handle mixed ticket updates through one atomic UpdateTicketCommand: lock once, validate all requested changes, persist once, record each changed field, and publish one update event after the transaction.
14. Migrate text-only ticket replies and internal notes through `AddTicketMessageCommand`; the handler persists transactionally and publishes the existing `TicketMessageCreated` event through an adapter.
15. Move ticket attachment uploads behind `AddTicketMessageWithAttachmentsCommand` and the `TicketAttachmentStorage` port. Laravel filesystem operations are isolated in an Infrastructure adapter; database metadata is persisted through the message repository, and stored files are cleaned up if persistence fails. Attachment downloads use `DownloadTicketAttachmentQuery` and the same storage port.
16. Introduce the SLA processing use case through `ProcessTicketSlaCommand` and `ProcessTicketSlaHandler`. The handler orchestrates the use case through `TicketSlaRepository` and `SlaPolicyProvider`. The repository atomically records the alert marker and outbox row; a separate dispatcher uses `SlaAlertOutboxRepository` and `SlaAlertPublisher` to hand notifications to Laravel. The scheduled console command delegates active-ticket selection to `TicketSlaRepository::eachActiveTicketId()` and invokes the handler for each ID; Eloquent query details remain in Infrastructure, and IDs are processed in bounded batches.
17. Introduce Insights read models and Communications adapters where they provide clear value.
18. Move remaining contexts gradually; avoid a big-bang rewrite.

## Naming and placement
Prefer business language in each context. Avoid generic global folders such as app/Services, app/DTOs or app/Repositories for new use cases. Existing legacy code can remain during migration, but new code should live inside its owning context.

The existing Eloquent models and HTTP controllers are legacy/framework-facing components until migrated. Do not rename or move them in bulk before routes, policies, jobs, listeners, tests and relationships have been adapted.

## Testing strategy
- Domain unit tests: no database, Laravel bootstrapping or mocks of Eloquent.
- Application tests: handlers with repository/clock/event contracts.
- Infrastructure integration tests: Eloquent mappings, transactions and database constraints.
- Presentation feature tests: HTTP authorization, validation, response contracts and end-to-end behavior.

## SLA domain rules
SLA policy targets are represented by the ServiceDesk domain value object `SlaPolicy`. Deadline evaluation is handled by the pure domain service `SlaDeadlineEvaluator`, which decides whether no action, a warning or a breach is due. It accepts date-time values and does not depend on Carbon, Eloquent, Laravel configuration or notification APIs.

`SlaPolicy` contains priority targets, the warning threshold and resolved statuses. `SlaDeadlineEvaluator` is a pure domain service that decides whether no action, a warning or a breach is due. The Application handler works with the framework-independent `TicketSlaSnapshot` DTO and depends on three ports: `TicketSlaRepository` for reading ticket state and atomically recording an alert marker with an outbox row, and `SlaPolicyProvider` for resolving configured policy. The `SlaAlertOutboxRepository` and `SlaAlertPublisher` ports are used by a separate dispatcher. Infrastructure implements these ports with Eloquent, Laravel configuration and the existing Laravel database notification. The outbox insert and ticket marker update share one database transaction; the scheduled dispatcher retries failed publication with capped exponential backoff.

The Eloquent repository uses conditional updates (`WHERE ... IS NULL`) to initialize deadlines and atomically create one outbox record per ticket/SLA-type/state. The dispatcher marks an outbox record as dispatched only after handing the notification to Laravel's notification system; failures remain pending with a retry delay. The scheduler prevents overlapping dispatcher runs. Delivery is at-least-once: a process crash after queue submission but before marking the outbox row dispatched can cause a duplicate queue submission, while Laravel's notification queue handles subsequent job retries. The handler contains no Eloquent, Carbon, Laravel configuration or notification dependencies. The old `TicketSlaService` and temporary processor adapter have been removed.


## Insights read side

The reports page is the first Insights query slice. The HTTP controller validates filter input and delegates to `BuildReportHandler`; the application query is represented by `BuildReportQuery`. The `ReportReadRepository` port isolates report-specific read access, while `EloquentReportReadRepository` owns SQL aggregation, joins and Eloquent option lists. This is intentionally a read-model boundary: report queries may use optimized SQL and do not need to load ServiceDesk aggregates. CSV export remains on the legacy controller path for a later extraction.
