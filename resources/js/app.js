import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import '@fullcalendar/core/index.css';
import '@fullcalendar/daygrid/index.css';
import '@fullcalendar/timegrid/index.css';

const themeKey = 'helpdesk-theme';
const accentKey = 'helpdesk-accent';
const availableAccents = ['indigo', 'blue', 'emerald', 'rose', 'amber', 'violet', 'cyan'];

function applyAccent(accent) {
    const selected = availableAccents.includes(accent) ? accent : 'indigo';
    document.documentElement.dataset.accent = selected;

    document.querySelectorAll('[data-accent-option]').forEach((option) => {
        option.setAttribute('aria-pressed', String(option.dataset.accentOption === selected));
    });
}

function applyTheme(theme) {
    const isDark = theme === 'dark';
    document.documentElement.dataset.theme = isDark ? 'dark' : 'light';

    const toggle = document.getElementById('theme-toggle');
    if (toggle) {
        toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        toggle.setAttribute('title', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        const icon = toggle.querySelector('.theme-toggle-icon');
        const label = toggle.querySelector('.theme-toggle-label');
        if (icon) icon.textContent = isDark ? '☀' : '☾';
        if (label) label.textContent = isDark ? 'Light mode' : 'Dark mode';
    }
}

const savedTheme = localStorage.getItem(themeKey);
const savedAccent = localStorage.getItem(accentKey);
applyTheme(savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));
applyAccent(savedAccent || 'indigo');

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(document.documentElement.dataset.theme || 'light');
    applyAccent(localStorage.getItem(accentKey) || 'indigo');

    document.querySelectorAll('[data-accent-option]').forEach((option) => {
        option.addEventListener('click', () => {
            const accent = option.dataset.accentOption;
            if (!availableAccents.includes(accent)) return;
            localStorage.setItem(accentKey, accent);
            applyAccent(accent);
        });
    });

    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        localStorage.setItem(themeKey, nextTheme);
        applyTheme(nextTheme);
    });
});


function initializeHelpDeskCalendar() {
    const root = document.getElementById('helpdesk-calendar');
    const calendarElement = document.getElementById('calendar-fullcalendar');

    if (!root || !calendarElement || root.dataset.initialized) return;
    root.dataset.initialized = 'true';

    const $ = (selector) => root.querySelector(selector);
    const dialog = $('#calendar-dialog');
    const form = $('#calendar-event-form');
    const agenda = $('#calendar-agenda-list');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const pad = (value) => String(value).padStart(2, '0');
    const toLocalInput = (value) => {
        const date = value instanceof Date ? value : new Date(value);
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
            + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    };
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character]);
    const eventKind = (event) => event.extendedProps.source === 'system'
        ? 'system'
        : (event.extendedProps.visibility || 'personal');
    const isVisible = (event) => {
        const filter = $('[data-calendar-filter="' + eventKind(event) + '"]');
        return !filter || filter.checked;
    };
    const updateUrl = (id) => root.dataset.updateUrl.replace('__ID__', encodeURIComponent(id));
    const headers = () => ({ Accept: 'application/json', 'X-CSRF-TOKEN': csrf });

    function showFormError(message) {
        const error = $('#calendar-form-error');
        error.textContent = message;
        error.hidden = false;
    }

    function openForm(event = null, start = calendar.getDate()) {
        form.reset();
        form.elements.event_id.value = event?.extendedProps.event_id || '';
        $('#calendar-dialog-title').textContent = event ? 'Edit event' : 'Create event';
        $('#calendar-delete').hidden = !event;
        $('#calendar-form-error').hidden = true;
        form.elements.title.value = event?.title || '';
        form.elements.description.value = event?.extendedProps.description || '';
        form.elements.type.value = event?.extendedProps.type || 'meeting';
        form.elements.visibility.value = event?.extendedProps.visibility || 'personal';
        if (form.elements.team_id) form.elements.team_id.value = event?.extendedProps.team_id || '';
        form.elements.ticket_id.value = event?.extendedProps.ticket?.id || '';
        form.elements.location.value = event?.extendedProps.location || '';
        form.elements.starts_at.value = toLocalInput(event?.start || start);
        form.elements.ends_at.value = event?.end ? toLocalInput(event.end) : '';
        form.elements.all_day.checked = Boolean(event?.allDay);
        if ($('#calendar-team-field')) {
            $('#calendar-team-field').hidden = form.elements.visibility.value !== 'team';
        }
        dialog.showModal();
    }

    function renderAgenda() {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const visibleEvents = calendar.getEvents()
            .filter(isVisible)
            .filter((event) => event.end ? event.end >= today : event.start >= today)
            .sort((a, b) => a.start - b.start)
            .slice(0, 8);

        $('#calendar-agenda-date').textContent = 'Coming up';
        agenda.replaceChildren();

        if (!visibleEvents.length) {
            const empty = document.createElement('p');
            empty.className = 'calendar-muted';
            empty.textContent = 'Nothing scheduled here. Enjoy the clear space!';
            agenda.append(empty);
            return;
        }

        visibleEvents.forEach((event) => {
            const props = event.extendedProps;
            const item = document.createElement('div');
            item.className = 'calendar-agenda-item';
            const dot = document.createElement('i');
            dot.className = 'calendar-agenda-dot ' + eventKind(event);
            const content = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = event.title;
            const time = document.createElement('small');
            time.textContent = event.allDay
                ? event.start.toLocaleDateString(undefined, { dateStyle: 'medium' })
                : event.start.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
            content.append(title, time);
            if (props.location) {
                const location = document.createElement('p');
                location.textContent = props.location;
                content.append(location);
            }
            if (props.ticket?.id) {
                const ticket = document.createElement('p');
                ticket.textContent = 'Ticket #' + props.ticket.id;
                content.append(ticket);
            }
            item.append(dot, content);
            item.addEventListener('click', () => {
                if (props.source === 'system' && event.url) {
                    window.location.assign(event.url);
                } else if (props.editable) {
                    openForm(event);
                }
            });
            agenda.append(item);
        });
    }

    async function persistEvent(event, revert) {
        const props = event.extendedProps;
        const payload = {
            title: event.title,
            description: props.description || null,
            type: props.type || 'meeting',
            visibility: props.visibility || 'personal',
            team_id: props.team_id || null,
            ticket_id: props.ticket?.id || null,
            location: props.location || null,
            starts_at: event.start.toISOString(),
            ends_at: event.end ? event.end.toISOString() : null,
            all_day: event.allDay ? 1 : 0,
        };

        try {
            const response = await fetch(updateUrl(props.event_id), {
                method: 'PATCH',
                headers: { ...headers(), 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            if (!response.ok) throw new Error('Unable to update this event.');
        } catch (error) {
            revert();
            window.alert(error.message || 'Unable to update this event.');
        }
    }

    const calendar = new Calendar(calendarElement, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        firstDay: 1,
        height: 'auto',
        nowIndicator: true,
        selectable: true,
        editable: true,
        dayMaxEvents: 3,
        navLinks: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        buttonText: { today: 'Today', month: 'Month', week: 'Week', day: 'Day' },
        events: {
            url: root.dataset.feedUrl,
            method: 'GET',
            failure: () => window.alert('Calendar events could not be loaded. Please try again.'),
        },
        eventDataTransform: (event) => ({
            ...event,
            classNames: ['visibility-' + (event.source === 'system' ? 'system' : event.visibility || 'personal')],
            editable: Boolean(event.editable),
            startEditable: Boolean(event.editable),
            durationEditable: Boolean(event.editable),
        }),
        eventClick(info) {
            if (info.event.extendedProps.source === 'system' && info.event.url) {
                info.jsEvent.preventDefault();
                window.location.assign(info.event.url);
                return;
            }
            if (info.event.extendedProps.editable) {
                info.jsEvent.preventDefault();
                openForm(info.event);
            }
        },
        dateClick(info) {
            openForm(null, info.date);
        },
        select(info) {
            openForm(null, info.start);
            calendar.unselect();
        },
        eventDrop(info) {
            persistEvent(info.event, info.revert);
        },
        eventResize(info) {
            persistEvent(info.event, info.revert);
        },
        eventsSet: renderAgenda,
        eventDidMount(info) {
            const description = info.event.extendedProps.description;
            if (description) info.el.title = info.event.title + ' — ' + description;
        },
    });

    $('#calendar-create').addEventListener('click', () => openForm());
    $('#calendar-close').addEventListener('click', () => dialog.close());
    $('#calendar-cancel').addEventListener('click', () => dialog.close());

    if ($('#calendar-visibility') && $('#calendar-team-field')) {
        $('#calendar-visibility').addEventListener('change', (event) => {
            $('#calendar-team-field').hidden = event.target.value !== 'team';
        });
    }

    root.querySelectorAll('[data-calendar-filter]').forEach((filter) => {
        filter.addEventListener('change', () => {
            calendar.getEvents().forEach((event) => {
                const visible = isVisible(event);
                const element = calendarElement.querySelector('[data-event-id="' + event.id + '"]');
                if (element) element.style.display = visible ? '' : 'none';
            });
            renderAgenda();
        });
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const payload = Object.fromEntries(new FormData(form).entries());
        const eventId = payload.event_id;
        delete payload.event_id;
        payload.all_day = form.elements.all_day.checked ? 1 : 0;
        payload.ticket_id = payload.ticket_id || null;
        payload.team_id = payload.team_id || null;
        payload.ends_at = payload.ends_at || null;

        const url = eventId ? updateUrl(eventId) : root.dataset.storeUrl;
        try {
            const response = await fetch(url, {
                method: eventId ? 'PATCH' : 'POST',
                headers: { ...headers(), 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                showFormError(Object.values(result.errors || {}).flat().join(' ') || result.message || 'Unable to save this event.');
                return;
            }
            dialog.close();
            calendar.refetchEvents();
        } catch (error) {
            showFormError(error.message || 'Unable to save this event.');
        }
    });

    $('#calendar-delete').addEventListener('click', async () => {
        const eventId = form.elements.event_id.value;
        if (!eventId || !window.confirm('Delete this calendar event?')) return;

        try {
            const response = await fetch(root.dataset.deleteUrl.replace('__ID__', encodeURIComponent(eventId)), {
                method: 'DELETE',
                headers: headers(),
            });
            if (!response.ok) throw new Error('Unable to delete this event.');
            dialog.close();
            calendar.refetchEvents();
        } catch (error) {
            showFormError(error.message || 'Unable to delete this event.');
        }
    });

    calendar.render();
}

document.addEventListener('DOMContentLoaded', initializeHelpDeskCalendar);
