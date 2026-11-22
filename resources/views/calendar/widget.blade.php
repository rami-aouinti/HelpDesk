<section class="panel helpdesk-calendar" id="helpdesk-calendar"
    data-feed-url="{{ route('calendar.feed') }}"
    data-store-url="{{ route('calendar.events.store') }}"
    data-update-url="{{ route('calendar.events.update', ['event' => '__ID__']) }}"
    data-delete-url="{{ route('calendar.events.destroy', ['event' => '__ID__']) }}">
    <div class="calendar-heading">
        <div>
            <span class="eyebrow">YOUR SCHEDULE</span>
            <h2>Calendar &amp; events</h2>
            <p>Personal reminders, team events and company-wide dates, together with ticket activity.</p>
        </div>
        <button type="button" class="btn btn-primary" id="calendar-create">＋ Add event</button>
    </div>

    <div class="calendar-filter-row" aria-label="Filter calendar events">
        <label><input type="checkbox" data-calendar-filter="personal" checked> <i class="calendar-legend personal"></i> Personal</label>
        <label><input type="checkbox" data-calendar-filter="team" checked> <i class="calendar-legend team"></i> Team</label>
        <label><input type="checkbox" data-calendar-filter="company" checked> <i class="calendar-legend company"></i> Company</label>
        <label><input type="checkbox" data-calendar-filter="system" checked> <i class="calendar-legend system"></i> Ticket activity</label>
    </div>

    <div class="calendar-layout">
        <div class="calendar-main">
            <div id="calendar-fullcalendar" aria-label="HelpDesk events calendar"></div>
        </div>
        <aside class="calendar-agenda">
            <div class="calendar-agenda-heading">
                <span class="eyebrow">AGENDA</span>
                <h3 id="calendar-agenda-date">Upcoming events</h3>
            </div>
            <div id="calendar-agenda-list" class="calendar-agenda-list" aria-live="polite">
                <p class="calendar-muted">Loading events…</p>
            </div>
        </aside>
    </div>

    <dialog class="calendar-dialog" id="calendar-dialog">
        <form id="calendar-event-form" class="calendar-event-form">
            <div class="calendar-dialog-heading">
                <div><span class="eyebrow">SCHEDULE</span><h3 id="calendar-dialog-title">Create event</h3></div>
                <button type="button" class="calendar-icon-button" id="calendar-close" aria-label="Close">×</button>
            </div>
            <input type="hidden" name="event_id">
            <label>Title <input name="title" maxlength="160" required placeholder="e.g. Weekly team sync"></label>
            <label>Description <textarea name="description" rows="3" maxlength="5000" placeholder="Add useful details…"></textarea></label>
            <div class="calendar-form-grid">
                <label>Event type
                    <select name="type">
                        <option value="meeting">Meeting</option><option value="reminder">Reminder</option>
                        <option value="maintenance">Maintenance</option><option value="training">Training</option>
                        <option value="announcement">Announcement</option><option value="holiday">Holiday</option><option value="other">Other</option>
                    </select>
                </label>
                <label>Visibility
                    <select name="visibility" id="calendar-visibility">
                        <option value="personal">Only me</option>@if ($calendarTeams->isNotEmpty())<option value="team">My team</option>@endif
                        @if (auth()->user()->hasRole('admin'))<option value="company">Everyone</option>@endif
                    </select>
                </label>
            </div>
            @if ($calendarTeams->isNotEmpty())
                <label id="calendar-team-field" hidden>Team
                    <select name="team_id">
                        <option value="">Select a team</option>
                        @foreach ($calendarTeams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
                    </select>
                </label>
            @endif
            <label>Related ticket (optional) <input name="ticket_id" type="number" min="1" placeholder="Ticket ID"></label>
            <div class="calendar-form-grid">
                <label>Starts <input name="starts_at" type="datetime-local" required></label>
                <label>Ends <input name="ends_at" type="datetime-local"></label>
            </div>
            <label>Location <input name="location" maxlength="180" placeholder="Meeting room or video link"></label>
            <label class="calendar-all-day"><input name="all_day" type="checkbox" value="1"> All-day event</label>
            <p class="calendar-form-error" id="calendar-form-error" role="alert" hidden></p>
            <div class="calendar-dialog-actions">
                <button type="button" class="btn btn-secondary" id="calendar-delete" hidden>Delete</button>
                <span></span>
                <button type="button" class="btn btn-secondary" id="calendar-cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" id="calendar-save">Save event</button>
            </div>
        </form>
    </dialog>
</section>

<style>
.helpdesk-calendar{margin:0 0 24px;padding:24px;--cal-border:var(--hd-border,rgba(148,163,184,.2));--cal-muted:var(--hd-muted,#8993a5);--cal-surface:var(--hd-surface,#fff)}
.calendar-heading,.calendar-dialog-heading,.calendar-dialog-actions{display:flex;align-items:center;justify-content:space-between;gap:14px}
.calendar-heading h2{font-size:1.25rem;margin:5px 0}.calendar-heading p{margin:0;color:var(--cal-muted);font-size:.88rem}
.calendar-filter-row{display:flex;gap:18px;flex-wrap:wrap;padding:15px 0;color:var(--cal-muted);font-size:.82rem}.calendar-filter-row label{display:flex;align-items:center;gap:7px;cursor:pointer}
.calendar-legend{width:9px;height:9px;border-radius:50%;display:inline-block}.calendar-legend.personal{background:#818cf8}.calendar-legend.team{background:#14b8a6}.calendar-legend.company{background:#f59e0b}.calendar-legend.system{background:#94a3b8}
.calendar-layout{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:22px}.calendar-main{min-width:0}
#calendar-fullcalendar{--fc-border-color:var(--cal-border);--fc-page-bg-color:transparent;--fc-neutral-bg-color:rgba(148,163,184,.08);--fc-list-event-hover-bg-color:rgba(99,102,241,.08);--fc-today-bg-color:rgba(99,102,241,.08);color:inherit}
#calendar-fullcalendar .fc{font-size:.84rem}#calendar-fullcalendar .fc .fc-toolbar{gap:12px;flex-wrap:wrap}#calendar-fullcalendar .fc .fc-toolbar-title{font-size:1.1rem;font-weight:700}
#calendar-fullcalendar .fc .fc-button{background:var(--hd-primary,#4f46e5);border-color:var(--hd-primary,#4f46e5);box-shadow:none;text-transform:capitalize;font-weight:600}
#calendar-fullcalendar .fc .fc-button:hover{filter:brightness(1.08)}#calendar-fullcalendar .fc .fc-button:disabled{opacity:.45}
#calendar-fullcalendar .fc .fc-button-primary:not(:disabled).fc-button-active{background:#312e81;border-color:#312e81}
#calendar-fullcalendar .fc .fc-daygrid-day-number{padding:7px;color:inherit;text-decoration:none}
#calendar-fullcalendar .fc .fc-col-header-cell-cushion{padding:10px 4px;color:var(--cal-muted);text-decoration:none;text-transform:uppercase;font-size:.72rem}
#calendar-fullcalendar .fc .fc-daygrid-event{border-radius:5px;padding:2px 4px;border-left:3px solid #818cf8;background:rgba(129,140,248,.14);color:inherit}
#calendar-fullcalendar .fc .fc-event.visibility-team{border-left-color:#14b8a6;background:rgba(20,184,166,.14)}
#calendar-fullcalendar .fc .fc-event.visibility-company{border-left-color:#f59e0b;background:rgba(245,158,11,.15)}
#calendar-fullcalendar .fc .fc-event.visibility-system{border-left-color:#94a3b8;background:rgba(148,163,184,.15)}
#calendar-fullcalendar .fc .fc-event-title{font-weight:600}#calendar-fullcalendar .fc .fc-timegrid-slot-label{color:var(--cal-muted)}
.calendar-agenda{border-left:1px solid var(--cal-border);padding-left:20px}.calendar-agenda-heading{margin-bottom:14px}.calendar-agenda-heading h3{font-size:.95rem;margin:5px 0 0}
.calendar-agenda-item{display:flex;gap:10px;padding:11px 0;border-bottom:1px solid var(--cal-border);cursor:pointer}.calendar-agenda-dot{width:9px;height:9px;border-radius:50%;background:#818cf8;flex:none;margin-top:5px}.calendar-agenda-dot.team{background:#14b8a6}.calendar-agenda-dot.company{background:#f59e0b}.calendar-agenda-dot.system{background:#94a3b8}
.calendar-agenda-item strong{display:block;font-size:.8rem}.calendar-agenda-item small,.calendar-muted{color:var(--cal-muted);font-size:.75rem}.calendar-agenda-item p{margin:3px 0 0;color:var(--cal-muted);font-size:.72rem}
.calendar-dialog{width:min(560px,calc(100% - 28px));max-height:90vh;overflow:auto;border:1px solid var(--cal-border);border-radius:16px;background:var(--cal-surface);color:var(--hd-ink,inherit);padding:0;box-shadow:0 24px 80px #0004}
.calendar-dialog::backdrop{background:rgba(15,23,42,.6);backdrop-filter:blur(3px)}.calendar-event-form{padding:22px;display:grid;gap:13px}
.calendar-dialog-heading{margin-bottom:4px}.calendar-dialog-heading h3{margin:4px 0;font-size:1.2rem}.calendar-event-form label{display:grid;gap:6px;font-size:.8rem;font-weight:600}
.calendar-event-form input:not([type=checkbox]),.calendar-event-form textarea,.calendar-event-form select{width:100%;min-width:0;border:1px solid var(--cal-border);border-radius:8px;background:transparent;color:inherit;padding:9px 10px;font:inherit;font-weight:400}
.calendar-event-form select option{color:#111827}.calendar-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.calendar-all-day{display:flex!important;align-items:center;gap:8px}
.calendar-form-error{color:#ef4444;font-size:.8rem;margin:0}.calendar-dialog-actions{padding-top:8px}.calendar-dialog-actions span{flex:1}
.calendar-icon-button{border:1px solid var(--cal-border);background:transparent;color:inherit;border-radius:9px;min-width:34px;height:34px;cursor:pointer;font-size:1.2rem}
@media(max-width:900px){.calendar-layout{grid-template-columns:1fr}.calendar-agenda{border-left:0;border-top:1px solid var(--cal-border);padding:18px 0 0}}
@media(max-width:600px){.helpdesk-calendar{padding:15px}.calendar-heading{align-items:flex-start;flex-direction:column}.calendar-form-grid{grid-template-columns:1fr}#calendar-fullcalendar .fc .fc-toolbar{align-items:flex-start}#calendar-fullcalendar .fc .fc-toolbar-title{font-size:1rem}}
</style>
