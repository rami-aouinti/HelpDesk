# Dashboard calendar

The dashboard calendar combines manually managed workspace events with selected system activity.

## Event visibility

- **Personal**: visible only to the event creator.
- **Team**: visible to members of the selected team.
- **Company**: visible to everyone; only administrators can publish company-wide events.
- Users can edit or delete their own events. Administrators can manage any event.

Events can include a title, description, type, location, start/end time, all-day flag, and an optional related ticket. Ticket references are checked against the same visibility rules used by the ticket workspace.

## System activity

Ticket creation dates are included automatically in the calendar feed for tickets the signed-in user is allowed to view. These entries are read-only and link back to the ticket.

## Dashboard interactions

The dashboard widget supports month, week, and day views, previous/next navigation, a Today shortcut, visibility filters, an upcoming agenda, and an event editor. Event data is loaded from the authenticated JSON feed.

## Routes

- `GET /calendar/events` — visible events in a requested date range (`start`, `end`).
- `POST /calendar/events` — create an event.
- `PATCH /calendar/events/{event}` — update an event the user may manage.
- `DELETE /calendar/events/{event}` — delete an event the user may manage.

## Deployment

Run the migration after pulling the change:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
```

Run the focused feature tests with:

```bash
docker compose exec app php artisan test --filter=CalendarEventsTest
```
