@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id)

@section('content')
    <div class="page-header">
        <div>
            <h1>Ticket #{{ $ticket->id }}</h1>
            <p>{{ $ticket->subject }}</p>
        </div>

        <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
            Back to tickets
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="content-card">
        <h2>Ticket information</h2>

        <p><strong>Subject:</strong> {{ $ticket->subject }}</p>
        <p><strong>Description:</strong> {{ $ticket->description }}</p>
        <p><strong>Customer:</strong> {{ $ticket->customer?->name ?? 'Unassigned' }}</p>
        <p><strong>Team:</strong> {{ $ticket->team?->name ?? 'Not assigned' }}</p>
        <p><strong>Assigned agent:</strong> {{ $ticket->assignedAgent?->name ?? 'Not assigned' }}</p>
        <p><strong>Created:</strong> {{ $ticket->created_at->format('d.m.Y H:i') }}</p>

        <hr>

        @can('update', $ticket)
        <h2>Update ticket</h2>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tickets.update', $ticket) }}">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    @foreach ([
                        'open',
                        'in_progress',
                        'waiting_for_customer',
                        'resolved',
                        'closed'
                    ] as $status)
                        <option value="{{ $status }}"
                            @selected(old('status', $ticket->status) === $status)>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="team_id">Team</label>
                <select name="team_id" id="team_id">
                    <option value="">No team</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) old('team_id', $ticket->team_id) === (string) $team->id)>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="assigned_to">Assigned agent</label>
                <select name="assigned_to" id="assigned_to">
                    <option value="">Unassigned</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected((string) old('assigned_to', $ticket->assigned_to) === (string) $agent->id)>
                            {{ $agent->name }} ({{ $agent->email }})
                        </option>
                    @endforeach
                </select>
                <small>The agent must belong to the selected team.</small>
            </div>

            <div class="form-group">
                <label for="priority">Priority</label>
                <select name="priority" id="priority">
                    @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                        <option value="{{ $priority }}"
                            @selected(old('priority', $ticket->priority) === $priority)>
                            {{ ucfirst($priority) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Save changes
            </button>
        </form>
        @else
            <p class="text-sm text-slate-500">Only support agents and administrators can update this ticket.</p>
        @endcan
    </div>

    <div class="content-card" style="margin-top: 24px;">
        <h2>Activity history</h2>

        @forelse ($ticket->histories as $history)
            <div style="padding: 14px 0; border-bottom: 1px solid #e2e8f0;">
                <strong>{{ ucfirst(str_replace('_', ' ', $history->action)) }}</strong>

                <p>
                    {{ $history->old_value ?? '—' }}
                    →
                    {{ $history->new_value ?? '—' }}
                </p>

                <small>
                    {{ $history->user?->name ?? 'System / Unauthenticated user' }}
                    · {{ $history->created_at->format('d.m.Y H:i') }}
                </small>
            </div>
        @empty
            <p>No activity recorded yet.</p>
        @endforelse
    </div>
@endsection
