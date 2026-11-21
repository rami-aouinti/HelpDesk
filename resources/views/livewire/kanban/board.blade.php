<div class="kanban-page">
    @if ($errors->has('board')) <div class="alert alert-error">{{ $errors->first('board') }}</div> @endif

    <section class="kanban-toolbar content-card">
        <div class="kanban-heading">
            <div><span class="kanban-eyebrow">DELIVERY WORKSPACE</span><h2>{{ $sprint?->name ?? 'Sprint Kanban' }}</h2>
                @if ($sprint)
                    <p>{{ $sprint->start_date->format('M d, Y') }} — {{ $sprint->end_date->format('M d, Y') }} · {{ $totalTickets }} visible tickets · {{ $doneTickets }} done</p>
                @else
                    <p>Create a sprint to start managing work on the Kanban board.</p>
                @endif
            </div>
            <div class="kanban-controls">
                <label class="kanban-select-label">Sprint
                    <select wire:change="selectSprint(Number($event.target.value))">
                        @forelse ($sprints as $option)
                            <option value="{{ $option->id }}" @selected($sprintId === $option->id)>{{ $option->name }}</option>
                        @empty
                            <option value="">No sprints</option>
                        @endforelse
                    </select>
                </label>
                <label class="kanban-mine-toggle"><input type="checkbox" wire:model.live="onlyMine"> <span>Only my assigned tickets</span></label>
                <a href="{{ route('sprints.index') }}" class="btn btn-secondary">Manage sprints</a>
            </div>
        </div>
        @if ($sprint && $totalTickets > 0)
            <div class="kanban-summary"><div class="kanban-summary-progress"><span style="width: {{ (int) round(($doneTickets / $totalTickets) * 100) }}%"></span></div><strong>{{ (int) round(($doneTickets / $totalTickets) * 100) }}%</strong><small>completed</small></div>
        @endif
    </section>

    @if (! $sprint)
        <section class="content-card kanban-empty"><div class="kanban-empty-icon">▦</div><h3>No sprint available</h3><p>Create your first sprint, then add tickets to it from the Kanban board.</p>@can('create', \App\Models\Sprint::class)<a href="{{ route('sprints.index') }}" class="btn btn-primary">Create a sprint</a>@endcan</section>
    @else
        <section class="kanban-backlog content-card">
            <div class="kanban-backlog-heading"><div><span class="kanban-eyebrow">UNSCHEDULED WORK</span><h3>Backlog</h3><p>Tickets that are not assigned to a sprint yet.</p></div><span class="kanban-count">{{ $backlog->count() }}</span></div>
            @if ($backlog->isNotEmpty())
                <div class="kanban-backlog-list">
                    @foreach ($backlog as $backlogTicket)
                        @php $canAddToSprint = auth()->user()->hasRole('admin') || (int) $backlogTicket->assigned_to === (int) auth()->id(); @endphp
                        <article class="kanban-backlog-ticket" wire:key="backlog-ticket-{{ $backlogTicket->id }}">
                            <div><span class="kanban-ticket-id">#{{ $backlogTicket->id }}</span><a href="{{ route('tickets.show', $backlogTicket) }}">{{ $backlogTicket->subject }}</a><small>{{ $backlogTicket->assignedAgent?->name ?? 'Unassigned' }} · {{ ucfirst($backlogTicket->priority) }}</small></div>
                            @if ($canAddToSprint && ! $sprint->is_completed)
                                <button type="button" class="btn btn-primary" wire:click="addTicketToSprint({{ $backlogTicket->id }})" wire:loading.attr="disabled">Add to sprint</button>
                            @elseif (! $canAddToSprint)
                                <small class="kanban-readonly-hint">Only the assignee or an administrator can move this ticket</small>
                            @else
                                <small class="kanban-readonly-hint">This sprint is already completed</small>
                            @endif
                        </article>
                    @endforeach
                </div>
            @else
                <p class="kanban-backlog-empty">No unplanned tickets are visible to you.</p>
            @endif
        </section>
        <div class="kanban-board" wire:loading.class="kanban-loading" wire:target="sprintId,onlyMine,moveTicketStatus,moveTicketToSprint,addTicketToSprint">
            @foreach ($columns as $column)
                <section class="kanban-column" wire:key="column-{{ $column['key'] }}" @dragover.prevent @drop.prevent="$wire.moveTicketStatus(Number($event.dataTransfer.getData('text/plain')), '{{ $column['key'] }}')">
                    <header class="kanban-column-header">
                        <div><span class="kanban-column-dot dot-{{ $column['key'] }}"></span><h3>{{ $column['label'] }}</h3></div>
                        <span class="kanban-count">{{ $column['tickets']->count() }}</span>
                    </header>
                    <div class="kanban-card-list">
                        @forelse ($column['tickets'] as $ticket)
                            @php $canMove = auth()->user()->hasRole('admin') || (int) $ticket->assigned_to === (int) auth()->id(); @endphp
                            <article class="kanban-ticket" wire:key="ticket-{{ $ticket->id }}" draggable="{{ $canMove ? 'true' : 'false' }}" @if($canMove) @dragstart="$event.dataTransfer.setData('text/plain', '{{ $ticket->id }}')" @endif>
                                <div class="kanban-ticket-top"><span class="kanban-ticket-id">#{{ $ticket->id }}</span><span class="kanban-priority priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></div>
                                <a href="{{ route('tickets.show', $ticket) }}" class="kanban-ticket-title">{{ $ticket->subject }}</a>
                                <p class="kanban-ticket-description">{{ \Illuminate\Support\Str::limit(strip_tags($ticket->description), 100) }}</p>
                                <div class="kanban-ticket-meta">
                                    <span title="Assigned agent">♙ {{ $ticket->assignedAgent?->name ?? 'Unassigned' }}</span>
                                    @if ($ticket->team)<span>{{ $ticket->team->name }}</span>@endif
                                </div>
                                @if ($canMove)
                                    <label class="kanban-move-label">Move to sprint
                                        <select wire:change="moveTicketToSprint({{ $ticket->id }}, Number($event.target.value))" aria-label="Move ticket {{ $ticket->id }} to another sprint">
                                            <option value="" selected>Current sprint</option>
                                            @foreach ($sprints->where('id', '!=', $sprint->id)->filter(fn ($candidate) => ! $candidate->is_completed) as $otherSprint)
                                                <option value="{{ $otherSprint->id }}">{{ $otherSprint->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <small class="kanban-drag-hint">⠿ Drag to change status</small>
                                @else
                                    <small class="kanban-readonly-hint">Assigned to another agent</small>
                                @endif
                            </article>
                        @empty
                            <div class="kanban-column-empty">Drop a ticket here</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
        <p class="kanban-permission-note">Agents can move only tickets assigned to them. Administrators can move every ticket. Status changes must respect the ticket workflow.</p>
    @endif

    <style>
        .kanban-page{min-width:0}.kanban-toolbar{padding:22px 24px;margin-bottom:18px}.kanban-heading{display:flex;align-items:center;justify-content:space-between;gap:24px}.kanban-eyebrow{font-size:10px;font-weight:800;letter-spacing:.14em;color:var(--muted,#64748b)}.kanban-heading h2{margin:5px 0;font-size:22px}.kanban-heading p{margin:0;color:var(--muted,#64748b);font-size:12px}.kanban-controls{display:flex;align-items:end;gap:12px;flex-wrap:wrap}.kanban-select-label,.kanban-move-label{display:flex;flex-direction:column;gap:5px;font-size:11px;font-weight:700;color:var(--muted,#64748b)}.kanban-select-label select,.kanban-move-label select{max-width:220px;padding:8px 10px;border:1px solid var(--border,#dbe1ea);border-radius:8px;background:var(--surface,#fff);color:var(--text,#111827);font-size:12px}.kanban-mine-toggle{display:flex;align-items:center;gap:7px;padding:9px 12px;border:1px solid var(--border,#dbe1ea);border-radius:8px;font-size:12px}.kanban-summary{display:flex;align-items:center;gap:9px;margin-top:18px}.kanban-summary-progress{height:6px;max-width:320px;flex:1;border-radius:99px;background:var(--surface-muted,#e5e7eb);overflow:hidden}.kanban-summary-progress span{display:block;height:100%;background:var(--primary,#4f46e5)}.kanban-summary strong{font-size:12px}.kanban-summary small{color:var(--muted,#64748b);font-size:11px}.kanban-board{display:grid;grid-template-columns:repeat(5,minmax(220px,1fr));gap:13px;align-items:start;overflow-x:auto;padding-bottom:12px}.kanban-column{min-height:360px;padding:12px;border:1px solid var(--border,#e2e8f0);border-radius:13px;background:var(--surface-muted,#f8fafc)}.kanban-column-header{display:flex;align-items:center;justify-content:space-between;padding:3px 2px 12px}.kanban-column-header>div{display:flex;align-items:center;gap:8px}.kanban-column-header h3{margin:0;font-size:12px;font-weight:800}.kanban-count{min-width:23px;padding:3px 7px;border-radius:99px;background:var(--surface,#fff);text-align:center;font-size:10px;font-weight:800}.kanban-column-dot{width:8px;height:8px;border-radius:50%;background:#64748b}.dot-in_progress{background:#3b82f6}.dot-waiting_for_customer{background:#d97706}.dot-resolved{background:#8b5cf6}.dot-closed{background:#16a34a}.kanban-card-list{display:flex;flex-direction:column;gap:9px;min-height:300px}.kanban-ticket{padding:12px;border:1px solid var(--border,#e2e8f0);border-radius:10px;background:var(--surface,#fff);box-shadow:0 1px 2px #0f172a0a}.kanban-ticket[draggable=true]{cursor:grab}.kanban-ticket[draggable=true]:active{cursor:grabbing}.kanban-ticket-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}.kanban-ticket-id{font-size:10px;font-weight:800;color:var(--muted,#64748b)}.kanban-priority{padding:3px 7px;border-radius:99px;font-size:9px;font-weight:800;background:#e2e8f0;color:#334155}.priority-urgent{background:#fee2e2;color:#b91c1c}.priority-high{background:#ffedd5;color:#9a3412}.priority-low{background:#dcfce7;color:#166534}.kanban-ticket-title{display:block;color:var(--text,#111827);font-size:12px;font-weight:800;text-decoration:none}.kanban-ticket-title:hover{color:var(--primary,#4f46e5)}.kanban-ticket-description{margin:6px 0 10px;color:var(--muted,#64748b);font-size:10px;line-height:1.5}.kanban-ticket-meta{display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;padding-top:8px;border-top:1px solid var(--border,#edf0f4);color:var(--muted,#64748b);font-size:9px}.kanban-move-label{margin-top:10px}.kanban-move-label select{width:100%;max-width:none;padding:6px;font-size:10px}.kanban-drag-hint,.kanban-readonly-hint{display:block;margin-top:8px;color:var(--muted,#94a3b8);font-size:9px}.kanban-readonly-hint{color:#b45309}.kanban-column-empty{display:grid;min-height:110px;place-items:center;border:1px dashed var(--border,#cbd5e1);border-radius:9px;color:var(--muted,#94a3b8);font-size:10px}.kanban-backlog{padding:16px 18px;margin-bottom:16px}.kanban-backlog-heading{display:flex;align-items:center;justify-content:space-between}.kanban-backlog-heading h3{margin:4px 0;font-size:14px}.kanban-backlog-heading p{margin:0;color:var(--muted,#64748b);font-size:11px}.kanban-backlog-list{display:flex;gap:10px;overflow-x:auto;padding-top:14px}.kanban-backlog-ticket{display:flex;align-items:center;justify-content:space-between;gap:14px;min-width:260px;padding:11px 12px;border:1px solid var(--border,#e2e8f0);border-radius:9px;background:var(--surface,#fff)}.kanban-backlog-ticket a{display:block;margin:3px 0;color:var(--text,#111827);font-size:11px;font-weight:800;text-decoration:none}.kanban-backlog-ticket small{display:block;color:var(--muted,#64748b);font-size:9px}.kanban-backlog-ticket .btn{white-space:nowrap;font-size:10px;padding:6px 9px}.kanban-backlog-empty{margin:14px 0 0;color:var(--muted,#64748b);font-size:11px}.kanban-permission-note{margin:8px 2px;color:var(--muted,#64748b);font-size:11px}.kanban-empty{text-align:center;padding:64px 20px}.kanban-empty-icon{font-size:42px;color:var(--muted,#94a3b8)}.kanban-empty h3{margin:10px 0 5px}.kanban-empty p{margin:0 0 18px;color:var(--muted,#64748b);font-size:13px}.kanban-loading{opacity:.55;pointer-events:none}@media(max-width:900px){.kanban-heading{align-items:flex-start;flex-direction:column}.kanban-board{grid-template-columns:repeat(5,minmax(235px,1fr))}}
    </style>
</div>
