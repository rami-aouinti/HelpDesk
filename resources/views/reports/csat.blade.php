@extends('layouts.app')
@section('title', 'Customer Satisfaction | HelpDesk')
@section('page-title', 'Customer Satisfaction')
@section('content')
<div class="page-header"><div><span class="eyebrow">QUALITY & EXPERIENCE</span><h1>Customer satisfaction</h1><p>Understand how customers rate their support experience.</p></div><a class="btn btn-secondary" href="{{ route('reports.index') }}">Back to reports</a></div>
<form method="GET" class="content-card mb-6 grid grid-cols-1 md:grid-cols-4 gap-3">
    <div class="form-group"><label for="from">From</label><input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}"></div>
    <div class="form-group"><label for="to">To</label><input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}"></div>
    <div class="form-group"><label for="agent_id">Agent</label><select id="agent_id" name="agent_id"><option value="">All agents</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected((string)($filters['agent_id'] ?? '') === (string)$agent->id)>{{ $agent->name }}</option>@endforeach</select></div>
    <div class="form-group"><label for="team_id">Team</label><select id="team_id" name="team_id"><option value="">All teams</option>@foreach($teams as $team)<option value="{{ $team->id }}" @selected((string)($filters['team_id'] ?? '') === (string)$team->id)>{{ $team->name }}</option>@endforeach</select></div>
    <div><button class="btn btn-primary">Apply filters</button></div>
</form>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="content-card"><span class="ticket-field-label">AVERAGE RATING</span><h2>{{ number_format((float)($summary->average_rating ?? 0), 2) }} / 5</h2><p>Across all matching responses</p></div>
    <div class="content-card"><span class="ticket-field-label">RESPONSES</span><h2>{{ $summary->responses ?? 0 }}</h2><p>Completed surveys</p></div>
    <div class="content-card"><span class="ticket-field-label">POSITIVE RATINGS</span><h2>{{ ($summary->responses ?? 0) ? number_format(100 * ($summary->positive_responses ?? 0) / $summary->responses, 1) : '0.0' }}%</h2><p>Ratings of 4 or 5 stars</p></div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <section class="content-card"><h2>By agent</h2>@forelse($byAgent as $row)<div class="flex justify-between py-3 border-b" style="border-color:var(--border-color)"><strong>{{ $row->agent?->name ?? 'Unassigned' }}</strong><span>★ {{ number_format((float)$row->average_rating, 2) }} · {{ $row->responses }} responses</span></div>@empty<p>No ratings for this period.</p>@endforelse</section>
    <section class="content-card"><h2>By team</h2>@forelse($byTeam as $row)<div class="flex justify-between py-3 border-b" style="border-color:var(--border-color)"><strong>{{ $row->team?->name ?? 'No team' }}</strong><span>★ {{ number_format((float)$row->average_rating, 2) }} · {{ $row->responses }} responses</span></div>@empty<p>No ratings for this period.</p>@endforelse</section>
</div>
<section class="content-card"><h2>Recent feedback</h2>@forelse($surveys as $survey)<article class="py-4 border-b" style="border-color:var(--border-color)"><div class="flex justify-between gap-3"><strong>#{{ $survey->ticket_id }} · {{ $survey->ticket?->subject }}</strong><span class="text-amber-500">{{ str_repeat('★', $survey->rating) }}{{ str_repeat('☆', 5 - $survey->rating) }}</span></div><p class="text-sm text-slate-500">{{ $survey->customer?->name }} · {{ $survey->agent?->name ?? 'Unassigned' }} · {{ $survey->submitted_at->format('d.m.Y') }}</p>@if($survey->comment)<p class="mt-2">{{ $survey->comment }}</p>@endif</article>@empty<p>No feedback has been submitted yet.</p>@endforelse<div class="mt-4">{{ $surveys->links() }}</div></section>
@endsection
