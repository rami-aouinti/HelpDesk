@extends('layouts.app')

@section('title', $team->name . ' | Team | HelpDesk')
@section('meta-description', 'Team members, ownership and assigned support tickets.')
@section('page-title', 'Team details')

@section('content')
    <div class="page-header"><div><span class="eyebrow">WORKSPACE / TEAMS</span><h1>{{ $team->name }}</h1><p>{{ $team->description ?: 'Team workspace and assigned workload.' }}</p></div><a href="{{ route('teams.index') }}" class="btn btn-secondary">Back to teams</a></div>
    <div class="hd-detail-grid"><div><span class="hd-detail-label">Members</span><strong>{{ $team->members->count() }}</strong></div><div><span class="hd-detail-label">Tickets</span><strong>{{ $team->tickets_count }}</strong></div><div><span class="hd-detail-label">Created</span><strong>{{ $team->created_at->format('d M Y') }}</strong></div></div>
    <section class="content-card"><div class="section-heading"><h2>Team members</h2><span class="section-count">{{ $team->members->count() }}</span></div>
        <div class="team-member-grid">@forelse($team->members as $member)<a class="team-member-profile" href="{{ route('users.show',$member) }}"><span class="hd-avatar">{{ strtoupper(mb_substr($member->name,0,1)) }}</span><span><strong>{{ $member->name }}</strong><small>{{ $member->email }}</small></span><span class="team-member-arrow">↗</span></a>@empty<p class="empty-state">No members assigned to this team.</p>@endforelse</div>
    </section>
    <section class="content-card"><div class="section-heading"><h2>Assigned tickets</h2><span class="section-count">{{ $team->tickets->count() }}</span></div>
        <div class="hd-record-list">@forelse($team->tickets as $ticket)<a href="{{ route('tickets.show',$ticket) }}"><span><strong>#{{ $ticket->id }} · {{ $ticket->subject }}</strong><small>{{ $ticket->customer?->name ?? 'No customer' }} · {{ $ticket->assignedAgent?->name ?? 'Unassigned' }}</small></span><span class="status status-{{ $ticket->status }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></a>@empty<p class="empty-state">No tickets assigned to this team.</p>@endforelse</div>
    </section>
    <style>.section-heading{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}.section-heading h2{font-size:18px;margin:0}.section-count{display:grid;place-items:center;min-width:30px;height:30px;border-radius:10px;background:var(--surface-muted,#f1f5f9);font-size:12px;font-weight:700}.team-member-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.team-member-profile{display:flex;align-items:center;gap:11px;padding:12px;border:1px solid var(--border,#e5e7eb);border-radius:12px;color:inherit;text-decoration:none;min-width:0}.team-member-profile>span:nth-child(2){display:grid;gap:3px;min-width:0}.team-member-profile small{font-size:11px;color:var(--muted,#64748b);overflow-wrap:anywhere}.team-member-arrow{margin-left:auto;color:var(--muted,#64748b)}@media(max-width:640px){.team-member-grid{grid-template-columns:1fr}}</style>
@endsection
