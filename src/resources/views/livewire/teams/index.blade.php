<div>
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="alert alert-error">{{ $errors->first() }}</div> @endif

    @if ($showForm)
        <section class="content-card team-form-card">
            <h2>{{ $teamId ? 'Edit team' : 'Create team' }}</h2>
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
            <div class="table-search"><label for="team-search">Search teams</label><input id="team-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Team name or description..."></div>
            <div class="table-filter"><label for="team-membership">Membership</label><select id="team-membership" wire:model.live="membership"><option value="">All teams</option><option value="with_members">With members</option><option value="without_members">Without members</option></select></div>
            <button type="button" class="btn btn-secondary toolbar-reset" wire:click="resetFilters">Reset filters</button>
            @can('create', \App\Models\Team::class)
                <button type="button" class="btn btn-primary toolbar-reset" wire:click="create">+ Create team</button>
            @endcan
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
                        <td>{{ $team->members_count }}<small>{{ $team->members->pluck('name')->join(', ') ?: 'No members assigned' }}</small></td>
                        <td>{{ $team->tickets_count }}</td>
                        <td class="actions">
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
</div>
