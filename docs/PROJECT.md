# HelpDesk — Project Definition

## 1. Product vision

HelpDesk is a multi-role customer-support application for a fictional software company. Customers use it to request assistance; support agents handle and communicate about requests; administrators manage users, teams, and service configuration.

The purpose is both educational and practical: every feature introduces a Laravel capability in a meaningful business context.

## 2. Objectives

- Build a usable ticket-management workflow from end to end.
- Learn Laravel idiomatically instead of recreating Symfony architecture mechanically.
- Understand the HTTP lifecycle, routing, controllers, middleware, validation, Eloquent, authentication, authorization, events, queues, notifications, APIs, and testing.
- Compare four frontend approaches in one coherent product.
- Run the development environment consistently through Docker Compose.

## 3. Users and roles

### Customer
- Register, sign in, and manage their own profile.
- Create a support ticket and describe the issue.
- View only tickets they are allowed to access.
- Reply to a conversation and attach files (later phase).
- Reopen a resolved ticket when permitted.

### Support agent
- View the tickets assigned to them or available to their team.
- Assign or accept tickets.
- Change ticket status and priority.
- Reply publicly to customers.
- Add internal notes that customers cannot see.
- Review ticket history.

### Administrator
- Manage users and roles.
- Create and manage support teams.
- Assign agents to teams.
- View all tickets and operational statistics.
- Configure categories and priorities.

Authorization must be enforced on the server for every protected action; hiding a button in the UI is not authorization.

## 4. Core domain concepts

The initial conceptual model contains:

- **User:** authenticated person with a role and profile.
- **Team:** group of support agents.
- **Ticket:** customer request with a reference, subject, description, status, priority, category, requester, and optional assignee/team.
- **TicketMessage:** public reply or private internal note belonging to a ticket.
- **TicketAttachment:** uploaded file associated with a message (later phase).
- **Category:** classification for incoming requests.
- **TicketActivity:** auditable record of important ticket changes (later phase).

These are product concepts, not a mandate to create every database table immediately. We will design migrations and Eloquent relationships when the learning roadmap reaches them.

## 5. Ticket lifecycle

Initial statuses:

1. **Open** — newly created and awaiting handling.
2. **In progress** — an agent is working on the request.
3. **Waiting for customer** — the agent needs more information.
4. **Resolved** — a proposed solution has been provided.
5. **Closed** — the conversation is complete.

Priorities: Low, Normal, High, Urgent.

A ticket has one requester and may have one assigned agent and one team. A ticket contains an ordered conversation. Internal notes are visible to agents and administrators only.

The allowed transitions and reopening rules will be made explicit in the business logic and tested.

## 6. Main functional modules

### Identity and access
- Registration, login, logout, password reset.
- Role-based access and policy-based authorization.
- Profile management.

### Ticket management
- Create, list, view, update, assign, and close tickets.
- Filter by status, priority, category, team, and assignee.
- Search by ticket reference or subject.
- Paginate lists.

### Conversations
- Public customer/agent replies.
- Private internal notes.
- Chronological message history.
- Notifications when a reply or assignment occurs.

### Administration
- User and team management.
- Categories and configurable priorities.
- Basic dashboard and operational metrics.

### Platform capabilities
- Email notifications and queued jobs.
- File attachments with validation and safe storage.
- Audit trail.
- REST API for ticket workflows.
- Automated tests and deployment-ready container configuration.

## 7. Frontend learning tracks

We will keep one backend and one product domain, then learn four ways to build its user experience.

### Track A — Blade
Laravel's server-rendered templating engine. Start here to understand routes, controllers, views, layouts, forms, validation errors, and CSRF protection. Build the first functional screens with Blade.

### Track B — Livewire
Build interactive server-driven components while keeping most application code in PHP. Reuse the same ticket use cases and compare component state, validation, events, pagination, and file uploads with the Blade implementation.

### Track C — Vue.js with Laravel API
Expose ticket use cases through a versioned JSON API and consume it from a Vue application. Learn API Resources, Sanctum authentication, request/response contracts, loading/error states, and frontend/backend separation.

### Track D — Inertia.js with Vue
Build a Vue single-page experience while retaining Laravel routes, controllers, validation, and server-side page props. Compare Inertia's workflow with a separately deployed API client.

**Important:** these are four learning tracks, not four frameworks required on every screen. We will identify what each approach solves, implement representative ticket workflows, and document trade-offs.

## 8. Quality and non-functional requirements

- Secure authentication, authorization, validation, and session handling.
- Customers must never access another customer's tickets or private agent notes.
- Passwords must be hashed using Laravel's supported mechanisms.
- Uploaded files must be size/type validated and not executable.
- Important business behaviour must have automated test coverage.
- Database schema changes must use migrations.
- Secrets and local environment files must not be committed.
- Errors and important operational events must be logged.
- Lists must be paginated; filters must be applied server-side.
- Code should follow Laravel conventions and PSR standards.

## 9. Initial scope and exclusions

The first MVP focuses on authentication, roles, ticket creation/management, assignment, and conversations.

Not part of the initial MVP: billing, SLA contracts, AI-generated answers, live chat, external CRM integrations, multi-company tenancy, and complex workflow automation. These may become later extension exercises after the core application is stable.

## 10. Definition of done for a feature

A feature is complete when:
- Its behaviour and permissions are clear.
- Validation and error cases are handled.
- Database changes are represented by migrations.
- Relevant automated tests pass.
- The implementation follows Laravel conventions.
- The documentation or learning notes are updated when a new concept is introduced.
