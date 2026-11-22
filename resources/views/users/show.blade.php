@extends('layouts.app')

@section('title', $managedUser->name . ' | User profile | HelpDesk')
@section('meta-description', 'View HelpDesk user profile, access role, team memberships and project assignments.')
@section('page-title', 'User profile')

@section('content')
    <div class="page-header">
        <div>
            <span class="eyebrow">WORKSPACE / USERS</span>
            <h1>{{ $managedUser->name }}</h1>
            <p>{{ $managedUser->email }}</p>
        </div>
        <div class="filter-actions">
            @can('update', $managedUser)
                <a href="{{ route('users.edit', $managedUser) }}" class="btn btn-primary">Edit user</a>
            @endcan
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Back to users</a>
        </div>
    </div>

    <div class="user-profile-layout">
        <section class="content-card user-profile-summary">
            <div class="user-profile-avatar">{{ strtoupper(mb_substr($managedUser->name, 0, 1)) }}</div>
            <h2>{{ $managedUser->name }}</h2>
            <p>{{ $managedUser->email }}</p>
            <span class="status {{ $managedUser->is_active ? 'status-resolved' : 'status-in_progress' }}">{{ $managedUser->is_active ? 'Active account' : 'Inactive account' }}</span>
            <div class="user-profile-meta">
                <div><span>Role</span><strong>{{ $managedUser->roles->pluck('name')->map(fn ($role) => ucfirst($role))->join(', ') ?: 'No role assigned' }}</strong></div>
                <div><span>Joined</span><strong>{{ $managedUser->created_at->format('d M Y') }}</strong></div>
                <div><span>Email verification</span><strong>{{ $managedUser->email_verified_at ? 'Verified' : 'Not verified' }}</strong></div>
            </div>
        </section>

        <div class="user-profile-content">
            <section class="content-card">
                <div class="section-heading"><div><span class="eyebrow">COLLABORATION</span><h2>Teams</h2></div><span class="section-count">{{ $managedUser->teams->count() }}</span></div>
                <div class="user-assignment-list">
                    @forelse ($managedUser->teams as $team)
                        <div class="user-assignment-row"><span class="hd-avatar">{{ strtoupper(mb_substr($team->name, 0, 1)) }}</span><div><strong>{{ $team->name }}</strong><small>{{ $team->description ?: 'Team member' }}</small></div></div>
                    @empty
                        <p class="empty-state">This user is not assigned to a team yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="content-card">
                <div class="section-heading"><div><span class="eyebrow">DELIVERY</span><h2>Projects</h2></div><span class="section-count">{{ $managedUser->projects->count() }}</span></div>
                <div class="user-assignment-list">
                    @forelse ($managedUser->projects as $project)
                        <div class="user-assignment-row"><span class="hd-avatar">{{ strtoupper(mb_substr($project->name, 0, 1)) }}</span><div><strong>{{ $project->name }}</strong><small>{{ ucfirst(str_replace('_', ' ', $project->status)) }}</small></div></div>
                    @empty
                        <p class="empty-state">This user is not assigned to a project yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <style>
        .user-profile-layout{display:grid;grid-template-columns:minmax(240px,.8fr) minmax(0,2fr);gap:20px;align-items:start}
        .user-profile-summary{text-align:center;padding:30px 24px}
        .user-profile-avatar{width:82px;height:82px;margin:0 auto 16px;display:grid;place-items:center;border-radius:26px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-size:30px;font-weight:800}
        .user-profile-summary h2{margin:0;font-size:21px}.user-profile-summary>p{margin:6px 0 16px;color:var(--muted,#64748b);font-size:13px}
        .user-profile-meta{display:grid;gap:0;margin-top:22px;text-align:left}.user-profile-meta>div{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-top:1px solid var(--border,#e5e7eb);font-size:12px}.user-profile-meta span{color:var(--muted,#64748b)}.user-profile-meta strong{text-align:right}
        .user-profile-content{display:grid;gap:20px}.section-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.section-heading h2{margin:4px 0 0;font-size:18px}.section-count{display:grid;place-items:center;min-width:30px;height:30px;border-radius:10px;background:var(--surface-muted,#f1f5f9);font-weight:700;font-size:12px}
        .user-assignment-list{display:grid;gap:9px}.user-assignment-row{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--border,#e5e7eb);border-radius:12px}.user-assignment-row>div{display:grid;gap:3px}.user-assignment-row small{color:var(--muted,#64748b);font-size:11px}
        @media(max-width:760px){.user-profile-layout{grid-template-columns:1fr}}
    </style>
@endsection
