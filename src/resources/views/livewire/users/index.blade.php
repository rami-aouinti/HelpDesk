<div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <section class="content-card">
        <div class="filters">
            <div class="form-group">
                <label for="user-search">Search</label>
                <input
                    id="user-search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Name or email"
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="user-role">Role</label>
                <select id="user-role" wire:model.live="role">
                    <option value="">All roles</option>
                    @foreach ($roles as $availableRole)
                        <option value="{{ $availableRole }}">{{ ucfirst($availableRole) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="user-status">Status</label>
                <select id="user-status" wire:model.live="status">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="filter-actions">
                <button
                    type="button"
                    class="btn btn-secondary"
                    wire:click="resetFilters"
                >Reset</button>
            </div>
        </div>

        <div class="table-wrapper" wire:loading.class="opacity-50" wire:target="search, role, status, gotoPage, nextPage, previousPage">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $user->email }}</small>
                            </td>
                            <td>{{ $user->roles->pluck('name')->map(fn ($name) => ucfirst($name))->join(', ') ?: 'No role' }}</td>
                            <td>
                                <span class="status {{ $user->is_active ? 'status-resolved' : 'status-in_progress' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $user->created_at->format('d M Y') }}</td>
                            <td class="actions">
                                <a class="btn btn-secondary" href="{{ route('users.edit', $user) }}">Edit</a>
                                @if (auth()->id() !== $user->id)
                                    <button
                                        type="button"
                                        class="btn {{ $user->is_active ? 'btn-danger' : 'btn-primary' }}"
                                        wire:click="toggleActive({{ $user->id }})"
                                        wire:confirm="Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} this user?"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleActive({{ $user->id }})"
                                    >
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">No users match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $users->links() }}</div>
        <div class="loading-indicator" wire:loading wire:target="search, role, status, toggleActive">
            Updating users...
        </div>
    </section>
</div>
