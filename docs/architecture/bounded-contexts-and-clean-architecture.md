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
5. Migrate update/claim/status transitions and ticket conversations incrementally.
6. Introduce Insights read models and Communications adapters where they provide clear value.
7. Move remaining contexts gradually; avoid a big-bang rewrite.

## Naming and placement

Prefer business language in each context. Avoid generic global folders such as app/Services, app/DTOs or app/Repositories for new use cases. Existing legacy code can remain during migration, but new code should live inside its owning context.

The existing Eloquent models and HTTP controllers are legacy/framework-facing components until migrated. Do not rename or move them in bulk before routes, policies, jobs, listeners, tests and relationships have been adapted.

## Testing strategy

- Domain unit tests: no database, Laravel bootstrapping or mocks of Eloquent.
- Application tests: handlers with repository/clock/event contracts.
- Infrastructure integration tests: Eloquent mappings, transactions and database constraints.
- Presentation feature tests: HTTP authorization, validation, response contracts and end-to-end behavior.