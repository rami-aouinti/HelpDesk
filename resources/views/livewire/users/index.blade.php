<div>
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="alert alert-error">{{ $errors->first() }}</div> @endif

    <section class="content-card data-table-card">
        <div class="table-toolbar">
            <div class="table-filter">
                <label for="user-role">Role</label>
                <select id="user-role" wire:model.live="role">
                    <option value="">All roles</option>
                    @foreach ($roles as $availableRole)<option value="{{ $availableRole }}">{{ ucfirst($availableRole) }}</option>@endforeach
                </select>
            </div>
            <div class="table-filter">
                <label for="user-status">Status</label>
                <select id="user-status" wire:model.live="status">
                    <option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="button" class="btn btn-secondary toolbar-reset" wire:click="resetFilters">Reset filters</button>
        </div>

        <div class="table-meta">
            <span>{{ $users->total() }} users</span>
            <label>Rows per page
                <select wire:model.live="perPage"><option value="10">10</option><option value="15">15</option><option value="25">25</option><option value="50">50</option></select>
            </label>
        </div>

        <div class="table-wrapper" wire:loading.class="table-loading" wire:target="search,role,status,perPage,sort,gotoPage,nextPage,previousPage">
            <table class="admin-data-table">
                <thead><tr>
                    <th><button wire:click="sort('name')" class="sort-button">User <span>{{ $sortBy === 'name' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th><button wire:click="sort('email')" class="sort-button">Email <span>{{ $sortBy === 'email' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th>Role</th><th>Status</th>
                    <th><button wire:click="sort('created_at')" class="sort-button">Joined <span>{{ $sortBy === 'created_at' ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕' }}</span></button></th>
                    <th>Actions</th>
                </tr></thead>
                <tbody>
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td><span class="hd-person"><span class="hd-avatar">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span><strong>{{ $user->name }}</strong></span></td><td>{{ $user->email }}</td>
                        <td>{{ $user->roles->pluck('name')->map(fn ($name) => ucfirst($name))->join(', ') ?: 'No role' }}</td>
                        <td><span class="status {{ $user->is_active ? 'status-resolved' : 'status-in_progress' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td class="actions">
                            <a class="btn btn-secondary" href="{{ route('users.show', $user) }}">Show</a>
                            @can('update', $user)
                                <a class="btn btn-secondary" href="{{ route('users.edit', $user) }}">Edit</a>
                            @endcan
                            @can('toggleActive', $user)
                                <button type="button" class="btn {{ $user->is_active ? 'btn-danger' : 'btn-primary' }}" wire:click="toggleActive({{ $user->id }})" wire:confirm="Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} this user?" wire:loading.attr="disabled">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>
                            @endcan
                        </td>
                    </tr>
                @empty <tr><td colspan="6" class="empty-state">No users match your filters.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        <div class="table-pagination">{{ $users->links('vendor.pagination.helpdesk') }}</div>
    </section>
    @if ($selectedUser)
        <div class="hd-modal-backdrop" wire:click.self="closeUser"><section class="content-card hd-modal-panel hd-detail-modal" role="dialog" aria-modal="true" aria-labelledby="user-detail-title">
            <div class="hd-modal-heading"><div><span class="hd-eyebrow">USER PROFILE</span><h2 id="user-detail-title">{{ $selectedUser->name }}</h2><p>{{ $selectedUser->email }}</p></div><button type="button" class="hd-modal-close" wire:click="closeUser" aria-label="Close">×</button></div>
            <div class="hd-detail-grid"><div><span class="hd-detail-label">Role</span><strong>{{ $selectedUser->roles->pluck('name')->map(fn ($name) => ucfirst($name))->join(', ') ?: 'No role' }}</strong></div><div><span class="hd-detail-label">Account status</span><strong>{{ $selectedUser->is_active ? 'Active' : 'Inactive' }}</strong></div><div><span class="hd-detail-label">Member since</span><strong>{{ $selectedUser->created_at->format('d M Y') }}</strong></div><div><span class="hd-detail-label">Email verification</span><strong>{{ $selectedUser->email_verified_at ? 'Verified' : 'Not verified' }}</strong></div></div>
            <h3>Teams</h3><div class="hd-avatar-list">@forelse($selectedUser->teams as $team)<span class="hd-chip"><span class="hd-avatar hd-avatar-sm">{{ strtoupper(mb_substr($team->name,0,1)) }}</span>{{ $team->name }}</span>@empty <span class="hd-muted">No teams assigned</span>@endforelse</div>
            <h3>Projects</h3><div class="hd-avatar-list">@forelse($selectedUser->projects as $project)<span class="hd-chip">{{ $project->name }}</span>@empty <span class="hd-muted">No projects assigned</span>@endforelse</div>
        </section></div>
    @endif
</div>
