<div>
    <section class="content-card data-table-card">
        <div class="table-toolbar">
            <div class="table-search"><label for="ticket-search">Search tickets</label><input id="ticket-search" wire:model.live.debounce.300ms="search" type="search" placeholder="Subject or description..."></div>
            <div class="table-filter"><label for="ticket-status">Status</label><select id="ticket-status" wire:model.live="status"><option value="">All statuses</option>@foreach (['open','in_progress','waiting_for_customer','resolved','closed'] as $option)<option value="{{ $option }}">{{ ucfirst(str_replace('_',' ',$option)) }}</option>@endforeach</select></div>
            <div class="table-filter"><label for="ticket-priority">Priority</label><select id="ticket-priority" wire:model.live="priority"><option value="">All priorities</option>@foreach (['low','normal','high','urgent'] as $option)<option value="{{ $option }}">{{ ucfirst($option) }}</option>@endforeach</select></div>
            @if (!auth()->user()->hasRole('customer'))
                <div class="table-filter"><label for="ticket-team">Team</label><select id="ticket-team" wire:model.live="team"><option value="">All teams</option>@foreach ($teams as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach</select></div>
            @endif
            <button type="button" class="btn btn-secondary toolbar-reset" wire:click="resetFilters">Reset</button>
        </div>
        <div class="table-meta"><span>{{ $tickets->total() }} tickets</span><label>Rows per page <select wire:model.live="perPage"><option value="10">10</option><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></label></div>
        <div class="table-wrapper" wire:loading.class="table-loading" wire:target="search,status,priority,team,perPage,sort,gotoPage,nextPage,previousPage">
            <table class="admin-data-table">
                <thead><tr>
                    <th><button wire:click="sort('id')" class="sort-button">ID <span>{{ $sortBy === 'id' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th><button wire:click="sort('subject')" class="sort-button">Subject <span>{{ $sortBy === 'subject' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th>Customer</th><th>Project</th><th>Team</th><th>Assigned agent</th>
                    <th><button wire:click="sort('priority')" class="sort-button">Priority <span>{{ $sortBy === 'priority' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th><button wire:click="sort('status')" class="sort-button">Status <span>{{ $sortBy === 'status' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th><button wire:click="sort('created_at')" class="sort-button">Created <span>{{ $sortBy === 'created_at' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                </tr></thead>
                <tbody>
                @forelse ($tickets as $ticket)
                    <tr wire:key="ticket-{{ $ticket->id }}">
                        <td>#{{ $ticket->id }}</td><td><a class="table-primary-link" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                        <td>{{ $ticket->customer?->name ?? 'Unassigned' }}</td><td>{{ $ticket->project?->name ?? '—' }}</td><td>{{ $ticket->team?->name ?? '—' }}</td><td>{{ $ticket->assignedAgent?->name ?? '—' }}</td>
                        <td><span class="priority-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td>
                        <td><span class="status status-{{ $ticket->status }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></td>
                        <td>{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty <tr><td colspan="9" class="empty-state">No tickets match your filters.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination">{{ $tickets->links('vendor.pagination.helpdesk') }}</div>
    </section>
</div>
