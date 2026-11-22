@extends('layouts.app')

@section('title', $project->name . ' | Project | HelpDesk')
@section('meta-description', 'Project overview, team members, documents and linked support tickets.')
@section('page-title', 'Project details')

@section('content')
    <div class="page-header">
        <div><span class="eyebrow">WORKSPACE / PROJECTS</span><h1>{{ $project->name }}</h1><p>{{ $project->description ?: 'Project overview and collaboration.' }}</p></div>
        <div class="filter-actions"><a href="{{ route('projects.index') }}" class="btn btn-secondary">Back to projects</a></div>
    </div>
    @if($project->cover_image_path)<div class="content-card project-detail-cover"><img src="{{ route('projects.cover', $project) }}" alt="{{ $project->name }} cover"></div>@endif
    <div class="hd-detail-grid project-summary-grid">
        <div><span class="hd-detail-label">Status</span><strong>{{ ucfirst(str_replace('_',' ',$project->status)) }}</strong></div>
        <div><span class="hd-detail-label">Budget</span><strong>{{ $project->budget !== null ? number_format((float)$project->budget,2).' €' : 'Not set' }}</strong></div>
        <div><span class="hd-detail-label">Start date</span><strong>{{ $project->start_date?->format('d M Y') ?? '—' }}</strong></div>
        <div><span class="hd-detail-label">End date</span><strong>{{ $project->end_date?->format('d M Y') ?? '—' }}</strong></div>
    </div>
    <div class="project-detail-columns">
        <section class="content-card"><div class="section-heading"><h2>Project members</h2><span class="section-count">{{ $project->members->count() }}</span></div>
            <div class="user-assignment-list">@forelse($project->members as $member)<a class="user-assignment-row" href="{{ route('users.show',$member) }}"><span class="hd-avatar">{{ strtoupper(mb_substr($member->name,0,1)) }}</span><div><strong>{{ $member->name }}</strong><small>{{ $member->email }}</small></div></a>@empty<p class="empty-state">No members assigned.</p>@endforelse</div>
        </section>
        <section class="content-card"><div class="section-heading"><h2>Documents</h2><span class="section-count">{{ $project->documents->count() }}</span></div>
            <div class="hd-record-list">@forelse($project->documents as $document)<a href="{{ route('projects.documents.download',$document) }}"><span><strong>{{ $document->original_name }}</strong><small>{{ number_format($document->size/1024,1) }} KB · uploaded by {{ $document->uploader?->name ?? 'Former user' }}</small></span><span>Download ↗</span></a>@empty<p class="empty-state">No documents uploaded.</p>@endforelse</div>
        </section>
    </div>
    <section class="content-card"><div class="section-heading"><h2>Project tickets</h2><span class="section-count">{{ $project->tickets->count() }}</span></div>
        <div class="hd-record-list">@forelse($project->tickets as $ticket)<a href="{{ route('tickets.show',$ticket) }}"><span><strong>#{{ $ticket->id }} · {{ $ticket->subject }}</strong><small>{{ $ticket->customer?->name ?? 'No customer' }} · {{ $ticket->assignedAgent?->name ?? 'Unassigned' }}</small></span><span class="status status-{{ $ticket->status }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></a>@empty<p class="empty-state">No tickets are linked to this project.</p>@endforelse</div>
    </section>
    <style>.project-detail-cover{padding:10px;margin-bottom:18px}.project-detail-cover img{display:block;width:100%;max-height:280px;object-fit:cover;border-radius:12px}.project-summary-grid{grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:18px}.project-detail-columns{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px}.section-heading{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}.section-heading h2{font-size:18px;margin:0}.section-count{display:grid;place-items:center;min-width:30px;height:30px;border-radius:10px;background:var(--surface-muted,#f1f5f9);font-size:12px;font-weight:700}.user-assignment-list{display:grid;gap:9px}.user-assignment-row{display:flex;align-items:center;gap:12px;padding:11px;border:1px solid var(--border,#e5e7eb);border-radius:12px;color:inherit;text-decoration:none}.user-assignment-row>div{display:grid;gap:3px}.user-assignment-row small{font-size:11px;color:var(--muted,#64748b)}@media(max-width:760px){.project-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.project-detail-columns{grid-template-columns:1fr}}</style>
@endsection
