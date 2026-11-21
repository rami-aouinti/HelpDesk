@extends('layouts.app')

@section('title', 'User management | HelpDesk')
@section('page-title', 'User management')

@section('content')
    <div class="page-header">
        <div>
            <h1>Users</h1>
            <p>Manage HelpDesk accounts, roles and access.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">+ Add user</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <section class="content-card">
        <form method="GET" action="{{ route('users.index') }}" class="filters">
            <div class="form-group">
                <label for="search">Search</label>
                <input id="search" name="search" value="{{ request('search') }}" placeholder="Name or email">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All statuses</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit">Apply filters</button>
                <a class="btn btn-secondary" href="{{ route('users.index') }}">Reset</a>
            </div>
        </form>

        <div class="table-wrapper">
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
                    <tr>
                        <td><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></td>
                        <td>{{ $user->roles->pluck('name')->map(fn ($role) => ucfirst($role))->join(', ') ?: 'No role' }}</td>
                        <td><span class="status {{ $user->is_active ? 'status-resolved' : 'status-in_progress' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td class="actions">
                            <a class="btn btn-secondary" href="{{ route('users.edit', $user) }}">Edit</a>
                            @if (auth()->id() !== $user->id)
                                <form method="POST" action="{{ route('users.toggle-active', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn {{ $user->is_active ? 'btn-danger' : 'btn-primary' }}" type="submit">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-state">No users match your filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $users->links() }}</div>
    </section>
@endsection
