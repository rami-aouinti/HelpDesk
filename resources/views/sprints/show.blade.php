@extends('layouts.app')

@section('title', $sprint->name . ' | Sprint | HelpDesk')
@section('meta-description', 'Sprint goal, delivery progress and linked tickets.')
@section('page-title', 'Sprint details')

@section('content')
    @php($progress = $sprint->progress)
    <div class="page-header"><div><span class="eyebrow">WORKSPACE / SPRINTS</span><h1>{{ $sprint->name }}</h1><p>{{ $sprint->description ?: 'Sprint delivery and workload overview.' }}</p></div><div class="filter-actions"><a href="{{ route('kanban.index', ['sprint' => $sprint->id]) }}" class="btn btn-primary">Open board</a><a href="{{ route('sprints.index') }}" class="btn btn-secondary">Back to sprints</a></div></div>
    <div class="hd-detail-grid"><div><span class="hd-detail-label">Period</span><strong>{{ $sprint->start_date->format('d M Y') }} — {{ $sprint->end_date->format('d M Y') }}</strong></div><div><span class="hd-detail-label">Total tickets</span><strong>{{ $sprint->tickets_count }}</strong></div><div><span class="hd-detail-label">Completed tickets</span><strong>{{ $sprint->done_tickets_count }}</strong></div><div><span class="hd-detail-label">Created by</span><strong>{{ $sprint->creator?->name ?? 'Unknown' }}</strong></div></div>
    <section class="content-card sprint-detail-progress"><div><span class="eyebrow">SPRINT COMPLETION</span><strong>{{ $progress }}%</strong></div><div class="hd-progress-track"><span style="width:{{ $progress }}%"></span></div><p>{{ $sprint->done_tickets_count }} of {{ $sprint->tickets_count }} tickets completed.</p></section>
    <section class="content-card"><div class="section-heading"><h2>Sprint tickets</h2><span class="section-count">{{ $sprint->tickets->count() }}</span></div>
        <div class="hd-record-list">@forelse($sprint->tickets as $ticket)<a href="{{ route('tickets.show',$ticket) }}"><span><strong>#{{ $ticket->id }} · {{ $ticket->subject }}</strong><small>{{ $ticket->customer?->name ?? 'No customer' }} · {{ $ticket->assignedAgent?->name ?? 'Unassigned' }}</small></span><span class="status status-{{ $ticket->status }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></a>@empty<p class="empty-state">No tickets have been added to this sprint.</p>@endforelse</div>
    </section>
    <style>.section-heading{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}.section-heading h2{font-size:18px;margin:0}.section-count{display:grid;place-items:center;min-width:30px;height:30px;border-radius:10px;background:var(--surface-muted,#f1f5f9);font-size:12px;font-weight:700}.sprint-detail-progress>div:first-child{display:flex;align-items:center;justify-content:space-between}.sprint-detail-progress>div:first-child strong{font-size:22px}.sprint-detail-progress p{margin:9px 0 0;color:var(--muted,#64748b);font-size:12px}</style>
@endsection
