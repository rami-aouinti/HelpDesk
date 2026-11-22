@extends('layouts.app')
@section('title', 'My Support Portal | HelpDesk')
@section('page-title', 'My Support Portal')
@section('content')
<div class="page-header">
    <div><span class="eyebrow">CUSTOMER SPACE</span><h1>Welcome, {{ auth()->user()->name }}</h1><p>Track your requests, talk to our support team and find answers.</p></div>
    <a class="btn btn-primary" href="{{ route('tickets.create') }}">＋ Create a ticket</a>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="content-card"><span class="ticket-field-label">ALL REQUESTS</span><h2>{{ $stats['total'] }}</h2></div>
    <div class="content-card"><span class="ticket-field-label">IN PROGRESS</span><h2>{{ $stats['open'] }}</h2></div>
    <div class="content-card"><span class="ticket-field-label">RESOLVED</span><h2>{{ $stats['resolved'] }}</h2></div>
</div>
<div class="content-card mb-6">
    <div class="ticket-section-heading"><div><span class="eyebrow">YOUR REQUESTS</span><h2>My tickets</h2><p>Only you can access the tickets listed here.</p></div></div>
    @forelse($tickets as $ticket)
        <a href="{{ route('tickets.show', $ticket) }}" class="flex items-center justify-between gap-4 py-4 border-b" style="text-decoration:none;border-color:var(--border-color)">
            <div><strong>#{{ $ticket->id }} · {{ $ticket->subject }}</strong><p class="text-sm text-slate-500">{{ $ticket->created_at->format('d.m.Y') }} · {{ $ticket->assignedAgent?->name ?? 'Waiting for an agent' }}</p></div>
            <span class="status status-{{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
        </a>
    @empty
        <p>You have not created any tickets yet.</p>
        <a class="btn btn-primary" href="{{ route('tickets.create') }}">Create your first ticket</a>
    @endforelse
    <div class="mt-4">{{ $tickets->links() }}</div>
</div>
<div class="content-card">
    <div class="ticket-section-heading"><div><span class="eyebrow">SELF SERVICE</span><h2>Help centre</h2><p>Find a quick answer before opening a new request.</p></div><a href="{{ route('knowledge-base.index') }}" class="btn btn-secondary">Browse all articles</a></div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @forelse($articles as $article)
            <a href="{{ route('knowledge-base.show', $article) }}" class="p-4 rounded-lg border" style="text-decoration:none;border-color:var(--border-color)"><strong>{{ $article->title }}</strong><p class="text-sm text-slate-500">{{ $article->excerpt ?: Str::limit(strip_tags($article->content), 120) }}</p></a>
        @empty
            <p>No published articles yet. You can still contact support by creating a ticket.</p>
        @endforelse
    </div>
</div>
@endsection
