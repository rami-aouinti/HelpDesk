<div>
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="alert alert-error">{{ $errors->first() }}</div> @endif

    @if ($showForm)
        <section class="content-card team-form-card hd-modal-panel" role="dialog" aria-modal="true" aria-labelledby="team-form-title">
            <div class="hd-modal-heading"><div><span class="hd-eyebrow">TEAM WORKSPACE</span><h2 id="team-form-title">{{ $teamId ? 'Edit team' : 'Create team' }}</h2></div><button type="button" class="hd-modal-close" wire:click="cancel" aria-label="Close">×</button></div>
            <form wire:submit="save">
                <div class="form-group"><label for="team-name">Team name</label><input id="team-name" type="text" wire:model="name" maxlength="255" required>@error('name') <small class="field-error">{{ $message }}</small> @enderror</div>
                <div class="form-group"><label for="team-description">Description</label><textarea id="team-description" wire:model="description" rows="3" maxlength="2000"></textarea>@error('description') <small class="field-error">{{ $message }}</small> @enderror</div>
                <div class="form-group"><label>Team members (active agents)</label>
                    @forelse ($agents as $agent)
                        <label class="team-member-option"><input type="checkbox" wire:model="members" value="{{ $agent->id }}"><span>{{ $agent->name }} <small>{{ $agent->email }}</small></span></label>
                    @empty <p>No active agents available. Create an agent account first.</p> @endforelse
                    @error('members') <small class="field-error">{{ $message }}</small> @enderror @error('members.*') <small class="field-error">{{ $message }}</small> @enderror
                </div>
                <div class="filter-actions"><button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Save team</button><button type="button" class="btn btn-secondary" wire:click="cancel">Cancel</button></div>
            </form>
        </section>
    @endif

    <section class="content-card data-table-card">
        <div class="table-toolbar">
            <div class="table-filter"><label for="team-membership">Membership</label><select id="team-membership" wire:model.live="membership"><option value="">All teams</option><option value="with_members">With members</option><option value="without_members">Without members</option></select></div>
            <button type="button" class="btn btn-secondary toolbar-reset" wire:click="resetFilters">Reset filters</button>
        </div>
        <div class="table-meta"><span>{{ $teams->total() }} teams</span><label>Rows per page <select wire:model.live="perPage"><option value="10">10</option><option value="15">15</option><option value="25">25</option><option value="50">50</option></select></label></div>
        <div class="table-wrapper" wire:loading.class="table-loading" wire:target="search,membership,perPage,sort,gotoPage,nextPage,previousPage">
            <table class="admin-data-table">
                <thead><tr>
                    <th><button wire:click="sort('name')" class="sort-button">Team <span>{{ $sortBy === 'name' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th><button wire:click="sort('members_count')" class="sort-button">Members <span>{{ $sortBy === 'members_count' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th><button wire:click="sort('tickets_count')" class="sort-button">Tickets <span>{{ $sortBy === 'tickets_count' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th><th>Actions</th>
                </tr></thead>
                <tbody>
                @forelse ($teams as $team)
                    <tr wire:key="team-{{ $team->id }}">
                        <td><strong>{{ $team->name }}</strong><small>{{ $team->description ?: 'No description' }}</small></td>
                        <td><div class="hd-avatar-list">@forelse($team->members->take(4) as $member)<span class="hd-avatar hd-avatar-sm" title="{{ $member->name }}">{{ strtoupper(mb_substr($member->name,0,1)) }}</span>@empty <span class="hd-muted">No members</span>@endforelse</div><small>{{ $team->members_count }} members</small></td>
                        <td>{{ $team->tickets_count }}</td>
                        <td class="actions">
                            <button type="button" class="btn btn-secondary" wire:click="show({{ $team->id }})">Show</button>
                            @can('update', $team)
                                <button type="button" class="btn btn-secondary" wire:click="edit({{ $team->id }})">Edit</button>
                            @endcan
                            @can('delete', $team)
                                <button type="button" class="btn btn-danger" wire:click="delete({{ $team->id }})" wire:confirm="Delete the team {{ $team->name }}?">Delete</button>
                            @endcan
                        </td>
                    </tr>
                @empty <tr><td colspan="4" class="empty-state">No teams match your filters.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination">{{ $teams->links('vendor.pagination.helpdesk') }}</div>
    </section>
    @if ($selectedTeam)
        <div class="hd-modal-backdrop" wire:click.self="closeDetails"><section class="content-card hd-modal-panel hd-detail-modal" role="dialog" aria-modal="true" aria-labelledby="team-detail-title">
            <div class="hd-modal-heading"><div><span class="hd-eyebrow">TEAM PROFILE</span><h2 id="team-detail-title">{{ $selectedTeam->name }}</h2><p>{{ $selectedTeam->description ?: 'No team description' }}</p></div><button type="button" class="hd-modal-close" wire:click="closeDetails" aria-label="Close">×</button></div>
            <div class="hd-detail-grid"><div><span class="hd-detail-label">Members</span><strong>{{ $selectedTeam->members->count() }}</strong></div><div><span class="hd-detail-label">Tickets</span><strong>{{ $selectedTeam->tickets_count }}</strong></div><div><span class="hd-detail-label">Created</span><strong>{{ $selectedTeam->created_at->format('d M Y') }}</strong></div></div>
            <h3>Team members</h3><div class="hd-member-list">@forelse($selectedTeam->members as $member)<div class="hd-member-row"><span class="hd-avatar">{{ strtoupper(mb_substr($member->name,0,1)) }}</span><span><strong>{{ $member->name }}</strong><small>{{ $member->email }}</small></span></div>@empty <p class="hd-muted">No members assigned yet.</p>@endforelse</div>
            <h3>Recent tickets</h3><div class="hd-record-list">@forelse($selectedTeam->tickets as $ticket)<a href="{{ route('tickets.show',$ticket) }}"><span>#{{ $ticket->id }} · {{ $ticket->subject }}</span><span class="status status-{{ $ticket->status }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></a>@empty <p class="hd-muted">No tickets assigned to this team.</p>@endforelse</div>
        </section></div>
    @endif
</div>
