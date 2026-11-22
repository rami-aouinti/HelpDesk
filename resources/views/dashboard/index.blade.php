@extends('layouts.app')

@section('title', 'Dashboard | HelpDesk')
@section('meta-description', 'Your HelpDesk overview: monitor ticket volume, priorities, team activity and the latest updates across your support workspace.')
@section('page-title', 'Dashboard')

@section('content')
    <div class="dashboard-intro">
        <div>
            <span class="eyebrow">{{ strtoupper($dashboardAudience) }}</span>
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

    <div class="stats-grid dashboard-stats-grid">
        @foreach ([
            ['key' => 'total', 'label' => 'Visible tickets', 'class' => 'dashboard-stat-total', 'hint' => 'In your workspace'],
            ['key' => 'open', 'label' => 'Open tickets', 'class' => 'dashboard-stat-open', 'hint' => 'Waiting to be handled'],
            ['key' => 'in_progress', 'label' => 'In progress', 'class' => 'dashboard-stat-progress', 'hint' => 'Currently being handled'],
            ['key' => 'resolved', 'label' => 'Resolved', 'class' => 'dashboard-stat-resolved', 'hint' => 'Successfully resolved'],
        ] as $stat)
            <article class="stat-card {{ $stat['class'] }}">
                <span class="dashboard-stat-label">
                    <i class="dashboard-stat-dot" aria-hidden="true"></i>
                    {{ $stat['label'] }}
                </span>
                <strong>{{ number_format($statistics[$stat['key']]) }}</strong>
                <small class="dashboard-stat-hint">{{ $stat['hint'] }}</small>
            </article>
        @endforeach
    </div>

    @include('calendar.widget')

    <div class="dashboard-insights-grid">
        <section class="panel dashboard-section dashboard-priority-panel">
            <div class="panel-heading">
                <div>
                    <span class="eyebrow">WORKLOAD</span>
                    <h2>Tickets by priority</h2>
                    <p>Understand what needs attention first.</p>
                </div>
                <span class="dashboard-total-pill">{{ number_format($statistics['total']) }} total</span>
            </div>

            <div class="priority-breakdown">
                @foreach ($priorityBreakdown as $priority)
                    <div class="priority-breakdown-item">
                        <div class="priority-breakdown-heading">
                            <span class="priority-breakdown-name">
                                <i class="priority-indicator priority-indicator-{{ $priority['key'] }}" aria-hidden="true"></i>
                                {{ $priority['label'] }}
                            </span>
                            <strong>{{ number_format($priority['count']) }}</strong>
                        </div>
                        <div class="priority-track" role="progressbar" aria-label="{{ $priority['label'] }} tickets" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $priority['percentage'] }}">
                            <span class="priority-fill priority-fill-{{ $priority['key'] }}" style="width: {{ $priority['percentage'] }}%"></span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel dashboard-section dashboard-activity-panel">
            <div class="panel-heading">
                <div>
                    <span class="eyebrow">LIVE UPDATES</span>
                    <h2>Recent activity</h2>
                    <p>The latest changes on tickets you can access.</p>
                </div>
            </div>

            @if ($recentActivity->isEmpty())
                <div class="dashboard-activity-empty">
                    <span aria-hidden="true">✦</span>
                    <p>No ticket updates yet.</p>
                </div>
            @else
                <div class="activity-list">
                    @foreach ($recentActivity as $activity)
                        @php
                            $activityLabel = match ($activity->action) {
                                'status' => 'changed the status',
                                'priority' => 'updated the priority',
                                'assigned_to' => 'updated the assignee',
                                'team_id' => 'updated the team',
                                'project_id' => 'updated the project',
                                'estimated_minutes' => 'updated the estimate',
                                'elapsed_minutes' => 'updated tracked time',
                                default => 'updated the ticket',
                            };
                            $activityValue = in_array($activity->action, ['status', 'priority'], true)
                                ? str_replace('_', ' ', (string) $activity->new_value)
                                : null;
                        @endphp
                        <a class="activity-item" href="{{ route('tickets.show', $activity->ticket) }}">
                            <span class="activity-marker" aria-hidden="true"></span>
                            <span class="activity-copy">
                                <strong>{{ $activity->user?->name ?? 'Someone' }}</strong>
                                <span>{{ $activityLabel }} <b>{{ $activity->ticket?->subject ?? 'a ticket' }}</b>@if ($activityValue) <em>to {{ $activityValue }}</em>@endif</span>
                                <small>{{ $activity->created_at->diffForHumans() }}</small>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
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
                            <th>Assigned to</th>
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
                                        @if ($ticket->customer?->profile_photo_path)
                                            <img class="dashboard-mini-avatar hd-avatar-photo" src="{{ \\Illuminate\\Support\\Facades\\Storage::url($ticket->customer->profile_photo_path) }}" alt="">
                                        @else
                                            <span class="dashboard-mini-avatar" aria-hidden="true">{{ strtoupper(substr($ticket->customer?->name ?? 'NA', 0, 2)) }}</span>
                                        @endif
                                        {{ $ticket->customer?->name ?? 'Unassigned' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="dashboard-customer">
                                        @if ($ticket->assignedAgent?->profile_photo_path)
                                            <img class="dashboard-mini-avatar hd-avatar-photo" src="{{ \\Illuminate\\Support\\Facades\\Storage::url($ticket->assignedAgent->profile_photo_path) }}" alt="">
                                        @else
                                            <span class="dashboard-mini-avatar" aria-hidden="true">{{ strtoupper(substr($ticket->assignedAgent?->name ?? '—', 0, 2)) }}</span>
                                        @endif
                                        {{ $ticket->assignedAgent?->name ?? 'Unassigned' }}
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
