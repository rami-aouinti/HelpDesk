<div>
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if ($errors->has('sprint')) <div class="alert alert-error">{{ $errors->first('sprint') }}</div> @endif

    @if ($showForm)
        <section class="content-card sprint-form-card hd-modal-panel" role="dialog" aria-modal="true" aria-labelledby="sprint-form-title">
            <div class="sprint-form-heading">
                <div><span class="sprint-eyebrow">SPRINT PLANNING</span><h2 id="sprint-form-title">{{ $sprintId ? 'Edit sprint' : 'Create sprint' }}</h2></div>
                <button type="button" class="btn btn-secondary" wire:click="cancel">Close</button>
            </div>
            <form wire:submit="save" class="sprint-form">
                <div class="form-group">
                    <label for="sprint-name">Sprint name</label>
                    <input id="sprint-name" type="text" wire:model="name" maxlength="255" placeholder="e.g. Sprint 01 — Customer portal" required>
                    @error('name') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="form-group">
                    <label for="sprint-description">Description</label>
                    <textarea id="sprint-description" wire:model="description" rows="3" maxlength="5000" placeholder="Sprint goal and scope..."></textarea>
                    @error('description') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="sprint-date-grid">
                    <div class="form-group"><label for="sprint-start">Start date</label><input id="sprint-start" type="date" wire:model="startDate" required>@error('startDate') <small class="field-error">{{ $message }}</small> @enderror</div>
                    <div class="form-group"><label for="sprint-end">End date</label><input id="sprint-end" type="date" wire:model="endDate" required>@error('endDate') <small class="field-error">{{ $message }}</small> @enderror</div>
                </div>
                @error('endDate') <small class="field-error">{{ $message }}</small> @enderror
                <div class="filter-actions"><button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save sprint</button><button type="button" class="btn btn-secondary" wire:click="cancel">Cancel</button></div>
            </form>
        </section>
    @endif

    <section class="content-card sprint-list-card">
        <div class="sprint-list-toolbar">
            <div><span class="sprint-eyebrow">DELIVERY PLANNING</span><h2>All sprints</h2><p>Plan iterations, track progress and keep ticket delivery on schedule.</p></div>
        </div>
        <div class="sprint-search-row">
            <label class="sprint-page-size">Rows <select wire:model.live="perPage"><option value="10">10</option><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></label>
        </div>

        @forelse ($sprints as $sprint)
            @php
                $completed = $sprint->tickets_count > 0 && $sprint->done_tickets_count === $sprint->tickets_count;
                $progress = $sprint->tickets_count > 0 ? (int) round(($sprint->done_tickets_count / $sprint->tickets_count) * 100) : 0;
            @endphp
            <article class="sprint-row" wire:key="sprint-{{ $sprint->id }}">
                <div class="sprint-row-main">
                    <div class="sprint-icon">◷</div>
                    <div class="sprint-row-copy">
                        <div class="sprint-row-title"><h3>{{ $sprint->name }}</h3>
                            @if ($completed) <span class="sprint-status is-complete">Completed</span>
                            @elseif (today()->lt($sprint->start_date)) <span class="sprint-status is-planned">Planned</span>
                            @elseif (today()->gt($sprint->end_date)) <span class="sprint-status is-overdue">Past due · {{ $progress }}%</span>
                            @else <span class="sprint-status is-active">Active</span> @endif
                        </div>
                        <p>{{ $sprint->description ?: 'No sprint goal added.' }}</p>
                        <div class="sprint-meta"><span>◷ {{ $sprint->start_date->format('M d, Y') }} — {{ $sprint->end_date->format('M d, Y') }}</span><span>♧ {{ $sprint->tickets_count }} tickets</span><span>✓ {{ $sprint->done_tickets_count }} done</span><span>By {{ $sprint->creator?->name ?? 'Unknown' }}</span></div>
                        <div class="sprint-progress"><span style="width: {{ $progress }}%"></span></div>
                    </div>
                    <strong class="sprint-progress-value">{{ $progress }}%</strong>
                </div>
                <div class="sprint-row-actions">
                    <button type="button" class="btn btn-secondary" wire:click="show({{ $sprint->id }})">Show</button>
                    <a class="btn btn-secondary" href="{{ route('kanban.index', ['sprint' => $sprint->id]) }}">Open board</a>
                    @can('update', $sprint)<button type="button" class="btn btn-secondary" wire:click="edit({{ $sprint->id }})">Edit</button>@endcan
                    @can('delete', $sprint)<button type="button" class="btn btn-danger" wire:click="delete({{ $sprint->id }})" wire:confirm="Delete {{ $sprint->name }}? This sprint must not contain tickets.">Delete</button>@endcan
                </div>
            </article>
        @empty
            <div class="sprint-empty"><span>◷</span><h3>No sprints yet</h3><p>Create your first sprint to start planning ticket delivery.</p></div>
        @endforelse
        <div class="table-pagination">{{ $sprints->links('vendor.pagination.helpdesk') }}</div>
    </section>

    @if ($selectedSprint)
        <div class="hd-modal-backdrop" wire:click.self="closeDetails"><section class="content-card hd-modal-panel hd-detail-modal" role="dialog" aria-modal="true" aria-labelledby="sprint-detail-title">
            <div class="hd-modal-heading"><div><span class="hd-eyebrow">SPRINT DETAILS</span><h2 id="sprint-detail-title">{{ $selectedSprint->name }}</h2><p>{{ $selectedSprint->description ?: 'No sprint goal added.' }}</p></div><button type="button" class="hd-modal-close" wire:click="closeDetails" aria-label="Close">×</button></div>
            <div class="hd-detail-grid"><div><span class="hd-detail-label">Period</span><strong>{{ $selectedSprint->start_date->format('d M Y') }} — {{ $selectedSprint->end_date->format('d M Y') }}</strong></div><div><span class="hd-detail-label">Tickets</span><strong>{{ $selectedSprint->tickets_count }}</strong></div><div><span class="hd-detail-label">Progress</span><strong>{{ $selectedSprint->progress }}%</strong></div><div><span class="hd-detail-label">Created by</span><strong>{{ $selectedSprint->creator?->name ?? 'Unknown' }}</strong></div></div>
            <div class="hd-progress-track"><span style="width:{{ $selectedSprint->progress }}%"></span></div><h3>Tickets in this sprint</h3><div class="hd-record-list">@forelse($selectedSprint->tickets as $ticket)<a href="{{ route('tickets.show',$ticket) }}"><span><strong>#{{ $ticket->id }} · {{ $ticket->subject }}</strong><small>{{ $ticket->customer?->name ?? 'No customer' }} · {{ $ticket->assignedAgent?->name ?? 'Unassigned' }}</small></span><span class="status status-{{ $ticket->status }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></a>@empty <p class="hd-muted">No tickets in this sprint yet.</p>@endforelse</div>
        </section></div>
    @endif

    <style>
        .sprint-form-card,.sprint-list-card{padding:26px;margin-bottom:20px}.sprint-form-heading,.sprint-list-toolbar,.sprint-search-row,.sprint-row,.sprint-row-main,.sprint-row-title,.sprint-row-actions,.sprint-meta{display:flex;align-items:center}.sprint-form-heading,.sprint-list-toolbar,.sprint-search-row{justify-content:space-between;gap:18px}.sprint-eyebrow{font-size:10px;font-weight:800;letter-spacing:.13em;color:var(--muted,#64748b)}.sprint-form-heading h2,.sprint-list-toolbar h2{margin:5px 0;font-size:21px}.sprint-list-toolbar p,.sprint-row-copy p{margin:3px 0;color:var(--muted,#64748b);font-size:13px}.sprint-form{max-width:760px;margin-top:20px}.sprint-date-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.sprint-row{justify-content:space-between;gap:20px;padding:20px 0;border-bottom:1px solid var(--border,#e5e7eb)}.sprint-row-main{min-width:0;flex:1;gap:14px}.sprint-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:12px;background:var(--surface-muted,#f1f5f9);font-size:22px}.sprint-row-copy{flex:1;min-width:0}.sprint-row-title{gap:10px;flex-wrap:wrap}.sprint-row-title h3{margin:0;font-size:15px}.sprint-status{padding:4px 9px;border-radius:99px;font-size:11px;font-weight:700}.is-complete{background:#dcfce7;color:#166534}.is-planned{background:#e0e7ff;color:#3730a3}.is-active{background:#dbeafe;color:#1d4ed8}.is-overdue{background:#ffedd5;color:#9a3412}.sprint-meta{gap:16px;flex-wrap:wrap;margin-top:10px;color:var(--muted,#64748b);font-size:11px}.sprint-progress{height:5px;margin-top:12px;border-radius:99px;background:var(--surface-muted,#e5e7eb);overflow:hidden}.sprint-progress span{display:block;height:100%;background:var(--primary,#4f46e5);border-radius:99px}.sprint-progress-value{min-width:42px;text-align:right;font-size:13px}.sprint-row-actions{gap:7px}.sprint-search-row{margin:22px 0 8px}.sprint-page-size{font-size:12px;color:var(--muted,#64748b)}.sprint-page-size select{margin-left:6px;padding:7px;border:1px solid var(--border,#ddd);border-radius:8px;background:var(--surface,#fff)}.sprint-empty{text-align:center;padding:54px 16px;color:var(--muted,#64748b)}.sprint-empty>span{font-size:34px}.sprint-empty h3{margin:8px 0;color:var(--text,#111827)}.sprint-empty p{margin:0;font-size:13px}@media(max-width:800px){.sprint-row{align-items:flex-start;flex-direction:column}.sprint-row-actions{width:100%;flex-wrap:wrap}.sprint-row-actions>*{flex:1}.sprint-list-toolbar{align-items:flex-start;flex-direction:column}.sprint-date-grid{grid-template-columns:1fr}}
    </style>
</div>
