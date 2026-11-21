@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id . ' | HelpDesk')
@section('page-title', 'Ticket #' . $ticket->id)

@section('content')
    <div class="page-header ticket-page-header">
        <div>
            <span class="eyebrow">SUPPORT WORKSPACE / TICKET</span>
            <h1>Ticket #{{ $ticket->id }}</h1>
            <p>{{ $ticket->subject }}</p>
            <div class="ticket-header-badges">
                <span class="status status-{{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                <span class="priority-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }} priority</span>
            </div>
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

    <div class="content-card ticket-information-card">
        <h2>Ticket overview</h2>
        <div class="ticket-overview">
            <div class="ticket-description">
                <span class="ticket-field-label">DESCRIPTION</span>
                <p>{{ $ticket->description }}</p>
            </div>
            <div class="ticket-detail-item"><span>Customer</span><strong>{{ $ticket->customer?->name ?? 'Unassigned' }}</strong></div>
            <div class="ticket-detail-item"><span>Project</span><strong>{{ $ticket->project?->name ?? 'No project' }}</strong></div>
            <div class="ticket-detail-item"><span>Team</span><strong>{{ $ticket->team?->name ?? 'Not assigned' }}</strong></div>
            <div class="ticket-detail-item"><span>Assigned agent</span><strong>{{ $ticket->assignedAgent?->name ?? 'Not assigned' }}</strong></div>
            <div class="ticket-detail-item"><span>Estimated time</span><strong>{{ intdiv($ticket->estimated_minutes, 60) }}h {{ $ticket->estimated_minutes % 60 }}m</strong></div>
            <div class="ticket-detail-item"><span>Elapsed time</span><strong>{{ intdiv($ticket->elapsed_minutes, 60) }}h {{ $ticket->elapsed_minutes % 60 }}m</strong></div>
            <div class="ticket-detail-item"><span>Remaining time</span><strong>{{ intdiv($ticket->remaining_minutes, 60) }}h {{ $ticket->remaining_minutes % 60 }}m</strong></div>
            <div class="ticket-detail-item"><span>Created</span><strong>{{ $ticket->created_at->format('d.m.Y H:i') }}</strong></div>
        </div>

        @if (auth()->user()->hasRole('agent') && $ticket->assigned_to === null)
            <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h3 class="font-semibold text-blue-900">Take ownership</h3>
                <p class="mt-1 text-sm text-blue-800">This ticket is waiting for a support agent.</p>

                @if ($errors->has('ticket') || $errors->has('team_id'))
                    <div class="alert alert-error mt-3">
                        {{ $errors->first('ticket') ?: $errors->first('team_id') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('tickets.claim', $ticket) }}" class="mt-3">
                    @csrf
                    @if ($ticket->team_id === null)
                        <div class="form-group">
                            <label for="claim-team-id">Choose your team</label>
                            <select name="team_id" id="claim-team-id" required>
                                <option value="">Select a team</option>
                                @foreach ($teams as $team)
                                    <option value="{{ $team->id }}" @selected((string) old('team_id') === (string) $team->id)>
                                        {{ $team->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary">Claim ticket</button>
                </form>
            </div>
        @endif

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

            @if (auth()->user()->hasRole('admin'))
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
            @endif

            <div class="form-group">
                <label for="estimated_minutes">Estimated time (minutes)</label>
                <input type="number" min="0" max="525600" name="estimated_minutes" id="estimated_minutes" value="{{ old('estimated_minutes', $ticket->estimated_minutes) }}">
            </div>
            <div class="form-group">
                <label for="elapsed_minutes">Elapsed time (minutes)</label>
                <input type="number" min="0" max="525600" name="elapsed_minutes" id="elapsed_minutes" value="{{ old('elapsed_minutes', $ticket->elapsed_minutes) }}">
                <small>Remaining time is calculated automatically.</small>
            </div>
            <div class="form-group"><label for="project_id">Project</label><select name="project_id" id="project_id"><option value="">No project</option>@foreach ($projects as $project)<option value="{{ $project->id }}" @selected((string) old('project_id', $ticket->project_id) === (string) $project->id)>{{ $project->name }}</option>@endforeach</select></div>
            @if (auth()->user()->hasRole('admin'))
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
            @endif

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


    <div class="content-card ticket-conversation-card" style="margin-top: 24px;">
        <div class="ticket-section-heading"><div><span class="eyebrow">MESSAGES</span><h2>Conversation</h2><p>Replies are visible to the customer and the support team.</p></div><span class="ticket-message-count">{{ $messages->count() }} messages</span></div>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="margin: 20px 0;">
            @forelse ($messages as $message)
                <article style="padding: 16px; margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: {{ $message->is_internal ? '#fffbeb' : '#ffffff' }};">
                    <div style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <strong>{{ $message->user->name }}</strong>
                        <small>{{ $message->created_at->format('d.m.Y H:i') }}</small>
                    </div>
                    @if ($message->is_internal)
                        <small style="display: inline-block; margin-top: 6px; color: #92400e; font-weight: 600;">Internal note · visible to support team only</small>
                    @endif
                    <p style="margin-top: 10px; white-space: pre-wrap;">{{ $message->body }}</p>

                    @if ($message->attachments->isNotEmpty())
                        <ul style="margin-top: 12px; padding-left: 20px; list-style: disc;">
                            @foreach ($message->attachments as $attachment)
                                <li style="margin-bottom: 6px;">
                                    <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                                       style="color: #2563eb; text-decoration: underline;">
                                        {{ $attachment->original_name }}
                                    </a>
                                    <small style="color: #64748b;">({{ number_format($attachment->size / 1024, 1) }} KB)</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            @empty
                <p>No replies yet. Start the conversation below.</p>
            @endforelse
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ route('tickets.messages.store', $ticket) }}">
            @csrf
            <div class="form-group">
                <label for="message-body">Your message</label>
                <textarea id="message-body" name="body" rows="5" maxlength="10000">{{ old('body') }}</textarea>
            </div>

            <div class="form-group">
                <label for="attachments">Attachments</label>
                <input
                    id="attachments"
                    type="file"
                    name="attachments[]"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png,.txt,.csv,.doc,.docx,.xls,.xlsx"
                >
                <small>Up to 5 files, 10 MB each. Allowed: PDF, images, text, CSV, Word and Excel files. You may send files without a message.</small>
            </div>

            @can('update', $ticket)
                <div class="form-group">
                    <label style="display: inline-flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_internal" value="1" @checked(old('is_internal'))>
                        Add as an internal note (customer cannot see this)
                    </label>
                </div>
            @endcan

            <button type="submit" class="btn btn-primary">Send message</button>
        </form>
    </div>

    <div class="content-card ticket-history-card" style="margin-top: 24px;">
        <div class="ticket-section-heading"><div><span class="eyebrow">AUDIT TRAIL</span><h2>Activity history</h2><p>Important changes made to this ticket.</p></div></div>

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
