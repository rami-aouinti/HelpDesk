@extends('layouts.app')

@section('title', 'Activity | HelpDesk')
@section('page-title', 'Activity')

@section('content')
    <div class="workspace-activity">
        <header class="workspace-activity-header">
            <div>
                <span class="eyebrow">WORKSPACE HISTORY</span>
                <h1>Activity</h1>
                <p>Follow changes across the tickets you are allowed to access.</p>
            </div>
            <span class="workspace-activity-count">{{ number_format($activities->total()) }} updates</span>
        </header>

        <form method="GET" action="{{ route('activity.index') }}" class="workspace-activity-filters">
            <label>
                <span>Activity type</span>
                <select name="action">
                    <option value="">All activity</option>
                    @foreach ([
                        'status' => 'Status changes',
                        'priority' => 'Priority changes',
                        'assigned_to' => 'Assignments',
                        'team_id' => 'Team changes',
                        'project_id' => 'Project changes',
                        'estimated_minutes' => 'Estimates',
                        'elapsed_minutes' => 'Tracked time',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Changed by</span>
                <select name="user">
                    <option value="">Everyone</option>
                    @foreach ($availableUsers as $availableUser)
                        <option value="{{ $availableUser->id }}" @selected((string) ($filters['user'] ?? '') === (string) $availableUser->id)>{{ $availableUser->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>From</span>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </label>
            <label>
                <span>To</span>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
            </label>
            <div class="workspace-activity-filter-actions">
                <button type="submit" class="btn btn-primary">Apply filters</button>
                <a href="{{ route('activity.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <section class="workspace-activity-feed" aria-label="Workspace activity">
            @forelse ($activities as $activity)
                @php
                    $labels = [
                        'status' => 'changed ticket status',
                        'priority' => 'changed ticket priority',
                        'assigned_to' => 'changed the assignee',
                        'team_id' => 'changed the team',
                        'project_id' => 'changed the project',
                        'estimated_minutes' => 'changed the estimate',
                        'elapsed_minutes' => 'updated tracked time',
                    ];
                    $label = $labels[$activity->action] ?? 'updated a ticket';
                    $value = in_array($activity->action, ['status', 'priority'], true)
                        ? str_replace('_', ' ', (string) $activity->new_value)
                        : $activity->new_value;
                @endphp
                <article class="workspace-activity-item">
                    <div class="workspace-activity-avatar">
                        @if ($activity->user?->profile_photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($activity->user->profile_photo_path) }}" alt="">
                        @else
                            {{ strtoupper(substr($activity->user?->name ?? 'NA', 0, 2)) }}
                        @endif
                    </div>
                    <div class="workspace-activity-body">
                        <div class="workspace-activity-main">
                            <p><strong>{{ $activity->user?->name ?? 'Someone' }}</strong> {{ $label }}
                                <a href="{{ route('tickets.show', $activity->ticket) }}">{{ $activity->ticket?->subject ?? 'a ticket' }}</a>
                            </p>
                            @if ($activity->ticket?->project)
                                <span class="workspace-activity-project">{{ $activity->ticket->project->name }}</span>
                            @endif
                            @if ($value !== null && $value !== '')
                                <div class="workspace-activity-change">
                                    @if ($activity->old_value !== null && $activity->old_value !== '')
                                        <span>{{ $activity->old_value }}</span>
                                        <span aria-hidden="true">→</span>
                                    @endif
                                    <strong>{{ $value }}</strong>
                                </div>
                            @endif
                        </div>
                        <time datetime="{{ $activity->created_at?->toIso8601String() }}">{{ $activity->created_at?->format('d M Y, H:i') }} · {{ $activity->created_at?->diffForHumans() }}</time>
                    </div>
                </article>
            @empty
                <div class="workspace-activity-empty">
                    <span aria-hidden="true">✦</span>
                    <h2>No activity found</h2>
                    <p>Try changing your filters. New ticket updates will appear here.</p>
                    @if (count($filters))
                        <a href="{{ route('activity.index') }}" class="btn btn-secondary">Clear filters</a>
                    @endif
                </div>
            @endforelse
        </section>

        @if ($activities->hasPages())
            <div class="workspace-activity-pagination">{{ $activities->links() }}</div>
        @endif
    </div>
@endsection
