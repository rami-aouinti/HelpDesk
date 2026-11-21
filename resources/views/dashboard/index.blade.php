@extends('layouts.app')

@section('title', 'Dashboard | HelpDesk')
@section('page-title', 'Dashboard')

@section('content')
    <div class="dashboard-intro">
        <div>
            <span class="eyebrow">WORKSPACE OVERVIEW</span>
            <h1>Good to see you, {{ auth()->user()->name }}.</h1>
            <p>Here is what is happening across your support workspace.</p>
        </div>
        <div class="dashboard-intro-actions">
            @can('tickets.create')
                <a href="{{ route('tickets.create') }}" class="btn btn-primary">＋ Create ticket</a>
            @endcan
            <a href="{{ route('tickets.index') }}" class="btn btn-secondary">View all tickets</a>
        </div>
    </div>

    <div class="stats-grid">
        @foreach ([
            ['key' => 'total', 'label' => 'Total tickets', 'class' => 'dashboard-stat-total'],
            ['key' => 'open', 'label' => 'Open tickets', 'class' => 'dashboard-stat-open'],
            ['key' => 'in_progress', 'label' => 'In progress', 'class' => 'dashboard-stat-progress'],
            ['key' => 'resolved', 'label' => 'Resolved', 'class' => 'dashboard-stat-resolved'],
        ] as $stat)
            <article class="stat-card {{ $stat['class'] }}">
                <span class="dashboard-stat-label">
                    <i class="dashboard-stat-dot" aria-hidden="true"></i>
                    {{ $stat['label'] }}
                </span>
                <strong>{{ number_format($statistics[$stat['key']]) }}</strong>
            </article>
        @endforeach
    </div>

    <section class="panel dashboard-section">
        <div class="panel-heading">
            <div>
                <span class="eyebrow">LATEST ACTIVITY</span>
                <h2>Recent tickets</h2>
                <p>Your latest support requests, sorted by creation date.</p>
            </div>
            <a href="{{ route('tickets.index') }}" class="dashboard-view-all">View all tickets →</a>
        </div>

        @if ($recentTickets->isEmpty())
            <div class="empty-state">
                <h3>Your workspace is clear</h3>
                <p>There are no tickets to display yet. New customer requests will appear here.</p>
                @can('tickets.create')
                    <a href="{{ route('tickets.create') }}" class="btn btn-primary">Create your first ticket</a>
                @endcan
            </div>
        @else
            <div class="table-wrapper">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Customer</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentTickets as $ticket)
                            <tr>
                                <td>
                                    <a class="dashboard-ticket-link" href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a>
                                    <span class="dashboard-ticket-id">Ticket #{{ $ticket->id }}</span>
                                </td>
                                <td>
                                    <span class="dashboard-customer">
                                        <span class="dashboard-mini-avatar" aria-hidden="true">{{ strtoupper(substr($ticket->customer?->name ?? 'NA', 0, 2)) }}</span>
                                        {{ $ticket->customer?->name ?? 'Unassigned' }}
                                    </span>
                                </td>
                                <td><span class="priority-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td>
                                <td><span class="status status-{{ $ticket->status }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span></td>
                                <td class="dashboard-date">{{ $ticket->created_at->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
