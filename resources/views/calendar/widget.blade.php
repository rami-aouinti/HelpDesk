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
    <div class="calendar-toolbar">
        <div class="calendar-month-control">
            <button type="button" class="calendar-icon-button" id="calendar-prev" aria-label="Previous period">‹</button>
            <button type="button" class="calendar-today" id="calendar-today">Today</button>
            <button type="button" class="calendar-icon-button" id="calendar-next" aria-label="Next period">›</button>
            <strong id="calendar-period-label" aria-live="polite"></strong>
        </div>
        <div class="calendar-view-switch" role="group" aria-label="Calendar view">
            <button type="button" data-calendar-view="month" class="is-active">Month</button>
            <button type="button" data-calendar-view="week">Week</button>
            <button type="button" data-calendar-view="day">Day</button>
        </div>
    </div>
    <div class="calendar-filter-row" aria-label="Filter calendar events">
        <label><input type="checkbox" data-calendar-filter="personal" checked> <i class="calendar-legend personal"></i> Personal</label>
        <label><input type="checkbox" data-calendar-filter="team" checked> <i class="calendar-legend team"></i> Team</label>
        <label><input type="checkbox" data-calendar-filter="company" checked> <i class="calendar-legend company"></i> Company</label>
        <label><input type="checkbox" data-calendar-filter="system" checked> <i class="calendar-legend system"></i> Ticket activity</label>
    </div>
    <div class="calendar-layout">
        <div class="calendar-main">
            <div class="calendar-weekdays" id="calendar-weekdays">
                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
            </div>
            <div class="calendar-grid" id="calendar-grid" aria-live="polite">
                <div class="calendar-loading">Loading your calendar…</div>
            </div>
        </div>
        <aside class="calendar-agenda">
            <div class="calendar-agenda-heading">
                <span class="eyebrow">AGENDA</span>
                <h3 id="calendar-agenda-date">Upcoming events</h3>
            </div>
            <div id="calendar-agenda-list" class="calendar-agenda-list">
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
            @if ($calendarTeams->isNotEmpty())<label id="calendar-team-field" hidden>Team
                <select name="team_id">
                    <option value="">Select a team</option>
                    @foreach ($calendarTeams as $team)<option value="{{ $team->id }}">{{ $team->name }}</option>@endforeach
                </select>
            </label>
            <label>Related ticket (optional)
                <input name="ticket_id" type="number" min="1" placeholder="Ticket ID">
            </label>
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
.calendar-heading,.calendar-toolbar,.calendar-month-control,.calendar-dialog-heading,.calendar-dialog-actions{display:flex;align-items:center;justify-content:space-between;gap:14px}
.calendar-heading h2{font-size:1.25rem;margin:5px 0}.calendar-heading p,.calendar-agenda-heading h3{margin:0;color:var(--cal-muted);font-size:.88rem}
.calendar-toolbar{margin-top:22px;padding:14px 0;border-top:1px solid var(--cal-border);border-bottom:1px solid var(--cal-border)}
.calendar-month-control{justify-content:flex-start;flex-wrap:wrap}.calendar-month-control strong{margin-left:8px;font-size:1rem}
.calendar-icon-button,.calendar-today,.calendar-view-switch button{border:1px solid var(--cal-border);background:transparent;color:inherit;border-radius:9px;min-width:34px;height:34px;cursor:pointer}
.calendar-today{padding:0 12px}.calendar-icon-button{font-size:1.2rem}.calendar-view-switch{display:flex;gap:4px;padding:3px;background:var(--cal-surface);border:1px solid var(--cal-border);border-radius:10px}.calendar-view-switch button{border:0;padding:0 12px;height:29px}.calendar-view-switch button.is-active{background:var(--hd-primary,#4f46e5);color:white}
.calendar-filter-row{display:flex;gap:18px;flex-wrap:wrap;padding:15px 0;color:var(--cal-muted);font-size:.82rem}.calendar-filter-row label{display:flex;align-items:center;gap:7px;cursor:pointer}.calendar-legend{width:9px;height:9px;border-radius:50%;display:inline-block}.calendar-legend.personal{background:#818cf8}.calendar-legend.team{background:#14b8a6}.calendar-legend.company{background:#f59e0b}.calendar-legend.system{background:#94a3b8}
.calendar-layout{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:22px}.calendar-weekdays,.calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr))}.calendar-weekdays span{text-align:center;padding:10px 2px;color:var(--cal-muted);font-size:.75rem;font-weight:700;text-transform:uppercase}
.calendar-day{border:1px solid var(--cal-border);border-right:0;border-bottom:0;min-height:105px;padding:7px 6px;min-width:0;background:transparent;cursor:pointer;text-align:left;color:inherit}.calendar-day:nth-child(7n){border-right:1px solid var(--cal-border)}.calendar-day:nth-last-child(-n+7){border-bottom:1px solid var(--cal-border)}.calendar-day.is-outside{opacity:.4}.calendar-day.is-selected{background:rgba(99,102,241,.08);box-shadow:inset 0 0 0 1px #818cf8}.calendar-day.is-today .calendar-day-number{background:var(--hd-primary,#4f46e5);color:#fff}.calendar-day-number{display:inline-flex;width:25px;height:25px;align-items:center;justify-content:center;border-radius:50%;font-size:.78rem;font-weight:700}
.calendar-event-chip{display:block;margin-top:4px;padding:3px 5px;border-radius:5px;font-size:.68rem;line-height:1.25;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;background:rgba(129,140,248,.14);color:inherit;border-left:3px solid #818cf8}.calendar-event-chip.team{border-color:#14b8a6;background:rgba(20,184,166,.12)}.calendar-event-chip.company{border-color:#f59e0b;background:rgba(245,158,11,.13)}.calendar-event-chip.system{border-color:#94a3b8;background:rgba(148,163,184,.13)}.calendar-more{font-size:.66rem;color:var(--cal-muted);padding:3px 4px}
.calendar-agenda{border-left:1px solid var(--cal-border);padding-left:20px}.calendar-agenda-heading{margin-bottom:14px}.calendar-agenda-heading h3{font-size:.95rem;margin-top:5px;color:inherit}.calendar-agenda-item{display:flex;gap:10px;padding:11px 0;border-bottom:1px solid var(--cal-border);cursor:pointer}.calendar-agenda-dot{width:9px;height:9px;border-radius:50%;background:#818cf8;flex:none;margin-top:5px}.calendar-agenda-dot.team{background:#14b8a6}.calendar-agenda-dot.company{background:#f59e0b}.calendar-agenda-dot.system{background:#94a3b8}.calendar-agenda-item strong{display:block;font-size:.8rem}.calendar-agenda-item small,.calendar-muted{color:var(--cal-muted);font-size:.75rem}.calendar-agenda-item p{margin:3px 0 0;color:var(--cal-muted);font-size:.72rem}.calendar-loading{grid-column:1/-1;padding:30px;text-align:center;color:var(--cal-muted)}
.calendar-dialog{width:min(560px,calc(100% - 28px));max-height:90vh;overflow:auto;border:1px solid var(--cal-border);border-radius:16px;background:var(--cal-surface);color:var(--hd-ink,inherit);padding:0;box-shadow:0 24px 80px #0004}.calendar-dialog::backdrop{background:rgba(15,23,42,.6);backdrop-filter:blur(3px)}.calendar-event-form{padding:22px;display:grid;gap:13px}.calendar-dialog-heading{margin-bottom:4px}.calendar-dialog-heading h3{margin:4px 0;font-size:1.2rem}.calendar-event-form label{display:grid;gap:6px;font-size:.8rem;font-weight:600}.calendar-event-form input:not([type=checkbox]),.calendar-event-form textarea,.calendar-event-form select{width:100%;min-width:0;border:1px solid var(--cal-border);border-radius:8px;background:transparent;color:inherit;padding:9px 10px;font:inherit;font-weight:400}.calendar-event-form select option{color:#111827}.calendar-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.calendar-all-day{display:flex!important;align-items:center;gap:8px}.calendar-form-error{color:#ef4444;font-size:.8rem;margin:0}.calendar-dialog-actions{padding-top:8px}.calendar-dialog-actions span{flex:1}
@media(max-width:900px){.calendar-layout{grid-template-columns:1fr}.calendar-agenda{border-left:0;border-top:1px solid var(--cal-border);padding:18px 0 0}.calendar-day{min-height:82px}.calendar-event-chip{font-size:.62rem}}
@media(max-width:600px){.helpdesk-calendar{padding:15px}.calendar-heading{align-items:flex-start;flex-direction:column}.calendar-toolbar{align-items:flex-start;flex-direction:column}.calendar-month-control{width:100%}.calendar-month-control strong{margin-left:auto}.calendar-day{min-height:65px;padding:4px 2px}.calendar-event-chip{height:5px;padding:0;width:70%;font-size:0;border-radius:4px}.calendar-form-grid{grid-template-columns:1fr}.calendar-filter-row{gap:10px}.calendar-weekdays span{font-size:.65rem}}
</style>
<script>
(function(){
 const root=document.getElementById('helpdesk-calendar');if(!root||root.dataset.initialized)return;root.dataset.initialized='1';
 const $=s=>root.querySelector(s),grid=$('#calendar-grid'),agenda=$('#calendar-agenda-list'),dialog=$('#calendar-dialog'),form=$('#calendar-event-form');
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
 let cursor=new Date(),selected=new Date(),mode='month',events=[];
 const pad=n=>String(n).padStart(2,'0'),dateKey=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
 const fmt=d=>new Intl.DateTimeFormat(undefined,{month:'long',year:'numeric'}).format(d);
 const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const typeClass=e=>e.source==='system'?'system':e.visibility;
 const enabled=e=>{const f=$('[data-calendar-filter="'+(e.source==='system'?'system':e.visibility)+'"]');return !f||f.checked};
 const dayEvents=d=>events.filter(e=>dateKey(new Date(e.start))===dateKey(d)&&enabled(e)).sort((a,b)=>new Date(a.start)-new Date(b.start));
 function range(){let start=new Date(cursor.getFullYear(),cursor.getMonth(),1),end=new Date(cursor.getFullYear(),cursor.getMonth()+1,1);if(mode==='week'){start=new Date(selected);start.setDate(start.getDate()-((start.getDay()+6)%7));end=new Date(start);end.setDate(end.getDate()+7)}if(mode==='day'){start=new Date(selected);start.setHours(0,0,0,0);end=new Date(start);end.setDate(end.getDate()+1)}return {start,end}}
 async function load(){const {start,end}=range();const url=new URL(root.dataset.feedUrl,location.origin);url.searchParams.set('start',start.toISOString());url.searchParams.set('end',end.toISOString());try{const res=await fetch(url,{headers:{Accept:'application/json'}});if(!res.ok)throw new Error('Calendar could not be loaded');events=(await res.json()).events||[];render()}catch(e){grid.innerHTML='<p class="calendar-loading">Calendar events are temporarily unavailable.</p>'}}
 function render(){const {start}=range();$('#calendar-period-label').textContent=mode==='month'?fmt(cursor):mode==='week'?'Week of '+start.toLocaleDateString():start.toLocaleDateString(undefined,{dateStyle:'full'});$('#calendar-weekdays').style.display=mode==='day'?'none':'grid';grid.style.gridTemplateColumns=mode==='day'?'1fr':'repeat(7,minmax(0,1fr))';grid.innerHTML='';
 let days=[];if(mode==='month'){let first=new Date(cursor.getFullYear(),cursor.getMonth(),1);let offset=(first.getDay()+6)%7;let startDay=new Date(first);startDay.setDate(first.getDate()-offset);for(let i=0;i<42;i++){let d=new Date(startDay);d.setDate(startDay.getDate()+i);days.push(d)}}else{let r=range();for(let d=new Date(r.start);d<r.end;d.setDate(d.getDate()+1))days.push(new Date(d))}
 days.forEach(d=>{let btn=document.createElement('button');btn.type='button';btn.className='calendar-day'+(mode==='month'&&d.getMonth()!==cursor.getMonth()?' is-outside':'')+(dateKey(d)===dateKey(selected)?' is-selected':'')+(dateKey(d)===dateKey(new Date())?' is-today':'');btn.setAttribute('aria-label',d.toLocaleDateString());let content='<span class="calendar-day-number">'+d.getDate()+'</span>';dayEvents(d).slice(0,3).forEach(e=>content+='<span class="calendar-event-chip '+typeClass(e)+'" title="'+esc(e.title)+'">'+esc(e.title)+'</span>');let more=dayEvents(d).length-3;if(more>0)content+='<span class="calendar-more">+'+more+' more</span>';btn.innerHTML=content;btn.addEventListener('click',()=>{selected=new Date(d);if(mode==='month'){}render()});grid.append(btn)});
 renderAgenda()}
 function renderAgenda(){const visible=events.filter(enabled).sort((a,b)=>new Date(a.start)-new Date(b.start));const upcoming=visible.filter(e=>new Date(e.start)>=new Date(dateKey(new Date())+'T00:00:00')).slice(0,7);$('#calendar-agenda-date').textContent=mode==='day'?selected.toLocaleDateString(undefined,{dateStyle:'medium'}):'Coming up';agenda.innerHTML='';let items=mode==='day'?dayEvents(selected):upcoming;if(!items.length){agenda.innerHTML='<p class="calendar-muted">Nothing scheduled here. Enjoy the clear space!</p>';return}items.forEach(e=>{let item=document.createElement('div');item.className='calendar-agenda-item';item.innerHTML='<i class="calendar-agenda-dot '+typeClass(e)+'"></i><div><strong>'+esc(e.title)+'</strong><small>'+(e.allDay?new Date(e.start).toLocaleDateString(undefined,{dateStyle:'medium'}):new Date(e.start).toLocaleString(undefined,{dateStyle:'medium',timeStyle:'short'}))+'</small>'+(e.location?'<p>'+esc(e.location)+'</p>':'')+(e.ticket?'<p>Ticket #'+e.ticket.id+'</p>':'')+'</div>';item.addEventListener('click',()=>{if(e.source==='system'&&e.url){location.href=e.url;return}if(e.editable)openForm(e)});agenda.append(item)})}
 function openForm(e=null,day=selected){form.reset();form.elements.event_id.value=e?.event_id||'';$('#calendar-dialog-title').textContent=e?'Edit event':'Create event';$('#calendar-delete').hidden=!e;$('#calendar-form-error').hidden=true;form.elements.title.value=e?.title||'';form.elements.description.value=e?.description||'';form.elements.type.value=e?.type||'meeting';form.elements.visibility.value=e?.visibility||'personal';if(form.elements.team_id)form.elements.team_id.value=e?.team_id||'';form.elements.ticket_id.value=e?.ticket?.id||'';form.elements.location.value=e?.location||'';form.elements.starts_at.value=e?toLocal(e.start):dateKey(day)+'T09:00';form.elements.ends_at.value=e?.end?toLocal(e.end):dateKey(day)+'T10:00';form.elements.all_day.checked=!!e?.allDay;if($('#calendar-team-field'))$('#calendar-team-field').hidden=form.elements.visibility.value!=='team';dialog.showModal()}
 function toLocal(s){const d=new Date(s);return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes())}
 $('#calendar-create').addEventListener('click',()=>openForm());$('#calendar-close').addEventListener('click',()=>dialog.close());$('#calendar-cancel').addEventListener('click',()=>dialog.close());
 if($('#calendar-visibility')&&$('#calendar-team-field'))$('#calendar-visibility').addEventListener('change',e=>$('#calendar-team-field').hidden=e.target.value!=='team');
 $('#calendar-prev').addEventListener('click',()=>{if(mode==='day')selected.setDate(selected.getDate()-1);else if(mode==='week')selected.setDate(selected.getDate()-7);else cursor.setMonth(cursor.getMonth()-1);if(mode!=='month')cursor=new Date(selected);load()});
 $('#calendar-next').addEventListener('click',()=>{if(mode==='day')selected.setDate(selected.getDate()+1);else if(mode==='week')selected.setDate(selected.getDate()+7);else cursor.setMonth(cursor.getMonth()+1);if(mode!=='month')cursor=new Date(selected);load()});
 $('#calendar-today').addEventListener('click',()=>{cursor=new Date();selected=new Date();load()});
 root.querySelectorAll('[data-calendar-view]').forEach(b=>b.addEventListener('click',()=>{mode=b.dataset.calendarView;root.querySelectorAll('[data-calendar-view]').forEach(x=>x.classList.toggle('is-active',x===b));cursor=new Date(selected);load()}));
 root.querySelectorAll('[data-calendar-filter]').forEach(b=>b.addEventListener('change',render));
 form.addEventListener('submit',async e=>{e.preventDefault();const data=Object.fromEntries(new FormData(form).entries());data.all_day=form.elements.all_day.checked?1:0;data.ticket_id=data.ticket_id||null;data.team_id=data.team_id||null;const id=data.event_id;delete data.event_id;const url=id?root.dataset.updateUrl.replace('__ID__',id):root.dataset.storeUrl;const res=await fetch(url,{method:id?'PATCH':'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify(data)});if(!res.ok){const payload=await res.json().catch(()=>({}));$('#calendar-form-error').textContent=Object.values(payload.errors||{}).flat().join(' ')||payload.message||'Unable to save this event.';$('#calendar-form-error').hidden=false;return}dialog.close();await load()});
 $('#calendar-delete').addEventListener('click',async()=>{const id=form.elements.event_id.value;if(!id||!confirm('Delete this calendar event?'))return;const res=await fetch(root.dataset.deleteUrl.replace('__ID__',id),{method:'DELETE',headers:{Accept:'application/json','X-CSRF-TOKEN':csrf}});if(!res.ok){$('#calendar-form-error').textContent='Unable to delete this event.';$('#calendar-form-error').hidden=false;return}dialog.close();await load()});
 load();
})();
</script>
