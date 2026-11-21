<div>
    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if ($errors->any()) <div class="alert alert-error">{{ $errors->first() }}</div> @endif

    <section class="content-card data-table-card">
        <div class="table-toolbar">
            <div class="table-search">
                <label for="user-search">Search users</label>
                <input id="user-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." autocomplete="off">
            </div>
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
                        <td><strong>{{ $user->name }}</strong></td><td>{{ $user->email }}</td>
                        <td>{{ $user->roles->pluck('name')->map(fn ($name) => ucfirst($name))->join(', ') ?: 'No role' }}</td>
                        <td><span class="status {{ $user->is_active ? 'status-resolved' : 'status-in_progress' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td class="actions">
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
</div>
