@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->id . ' | HelpDesk')
@section('meta-description', 'View the ticket conversation, review its status and priority, exchange updates and coordinate the resolution with your support team.')
@section('page-title', 'Ticket #' . $ticket->id)

@section('content')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.location.hash === '#edit-ticket' || new URLSearchParams(window.location.search).get('edit') === '1') document.getElementById('ticket-edit-dialog')?.showModal();
        });
    </script>
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

    <div class="content-card ticket-information-card ticket-detail-section">
        <h2>Ticket overview</h2>
        <div class="ticket-overview">
            <div class="ticket-description">
                <span class="ticket-field-label">DESCRIPTION</span>
                <p>{{ $ticket->description }}</p>
            </div>
            <div class="ticket-detail-item"><span>Customer</span><strong class="hd-person">@if($ticket->customer?->profile_photo_path)<img class="hd-avatar hd-avatar-photo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->customer->profile_photo_path) }}" alt="">@else<span class="hd-avatar">{{ strtoupper(mb_substr($ticket->customer?->name ?? 'U',0,1)) }}</span>@endif{{ $ticket->customer?->name ?? 'Unassigned' }}</strong></div>
            <div class="ticket-detail-item"><span>Project</span><strong>{{ $ticket->project?->name ?? 'No project' }}</strong></div>
            <div class="ticket-detail-item"><span>Sprint</span><strong>{{ $ticket->sprint?->name ?? 'No sprint' }}</strong></div>
            <div class="ticket-detail-item"><span>Team</span><strong>{{ $ticket->team?->name ?? 'Not assigned' }}</strong></div>
            <div class="ticket-detail-item"><span>Assigned agent</span><strong class="hd-person">@if($ticket->assignedAgent)@if($ticket->assignedAgent->profile_photo_path)<img class="hd-avatar hd-avatar-photo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ticket->assignedAgent->profile_photo_path) }}" alt="{{ $ticket->assignedAgent->name }}">@else<span class="hd-avatar">{{ strtoupper(mb_substr($ticket->assignedAgent->name,0,1)) }}</span>@endif{{ $ticket->assignedAgent->name }}@else Not assigned @endif</strong></div>
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
        <div class="ticket-edit-launch"><div><h2>Ticket settings</h2><p>Change status, priority, assignment and planning details.</p></div><button type="button" class="btn btn-primary" onclick="document.getElementById('ticket-edit-dialog').showModal()">Edit ticket</button></div>
        <dialog class="hd-ticket-dialog" id="ticket-edit-dialog" aria-labelledby="ticket-edit-title">
            <div class="hd-modal-heading"><div><span class="hd-eyebrow">TICKET #{{ $ticket->id }}</span><h2 id="ticket-edit-title">Edit ticket</h2><p>Update the ticket and save your changes.</p></div><button type="button" class="hd-modal-close" aria-label="Close" onclick="document.getElementById('ticket-edit-dialog').close()">×</button></div>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tickets.update', $ticket) }}?edit=1">
            @csrf
            @method('PATCH')

            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status">
                    @php
                        $statusOptions = auth()->user()->hasRole('admin')
                            ? ['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed']
                            : array_values(array_unique(array_merge(
                                [$ticket->status],
                                match ($ticket->status) {
                                    'open' => ['in_progress'],
                                    'in_progress' => ['waiting_for_customer', 'resolved'],
                                    'waiting_for_customer' => ['in_progress'],
                                    'resolved' => ['closed'],
                                    default => [],
                                },
                            )));
                    @endphp
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}"
                            @selected(old('status', $ticket->status) === $status)>
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if (auth()->user()->hasRole('admin'))
            <div class="form-group">
                <label>Team</label>
                <div class="hd-choice-grid">
                    <label class="hd-choice-card"><input type="radio" name="team_id" value="" @checked((string) old('team_id', $ticket->team_id ?? '') === '')><span class="hd-avatar">—</span><span><strong>No team</strong><small>Keep this ticket unassigned to a team</small></span></label>
                    @foreach ($teams as $team)
                        <label class="hd-choice-card"><input type="radio" name="team_id" value="{{ $team->id }}" @checked((string) old('team_id', $ticket->team_id) === (string) $team->id)><span class="hd-avatar">{{ strtoupper(mb_substr($team->name,0,1)) }}</span><span><strong>{{ $team->name }}</strong><small>Team assignment</small></span></label>
                    @endforeach
                </div>
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
                <label>Assigned agent</label>
                <div class="hd-choice-grid">
                    <label class="hd-choice-card"><input type="radio" name="assigned_to" value="" @checked((string) old('assigned_to', $ticket->assigned_to ?? '') === '')><span class="hd-avatar">—</span><span><strong>Unassigned</strong><small>No agent selected</small></span></label>
                    @foreach ($agents as $agent)
                        <label class="hd-choice-card"><input type="radio" name="assigned_to" value="{{ $agent->id }}" @checked((string) old('assigned_to', $ticket->assigned_to) === (string) $agent->id)><span class="hd-avatar">{{ strtoupper(mb_substr($agent->name,0,1)) }}</span><span><strong>{{ $agent->name }}</strong><small>{{ $agent->email }}</small></span></label>
                    @endforeach
                </div>
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
        </dialog>
        @else
            <p class="text-sm text-slate-500">Only support agents and administrators can update this ticket.</p>
        @endcan
    </div>


    <div class="content-card ticket-conversation-card ticket-detail-section">
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

        <div class="ticket-message-list">
            @forelse ($messages as $message)
                <article class="ticket-message {{ $message->is_internal ? 'ticket-message-internal' : '' }}">
                    <div class="ticket-message-avatar" aria-hidden="true">@if($message->user?->profile_photo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($message->user->profile_photo_path) }}" alt="">@else{{ strtoupper(substr($message->user?->name ?? 'HD', 0, 2)) }}@endif</div>
                    <div class="ticket-message-content">
                        <div class="ticket-message-meta">
                            <div class="ticket-message-author">
                                <strong>{{ $message->user?->name ?? 'Former user' }}</strong>
                                @if ($message->is_internal)
                                    <span class="ticket-internal-label">Internal note</span>
                                @else
                                    <span class="ticket-reply-label">Public reply</span>
                                @endif
                            </div>
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d.m.Y · H:i') }}</time>
                        </div>
                        <p class="ticket-message-body">{{ $message->body }}</p>

                        @if ($message->attachments->isNotEmpty())
                            <div class="ticket-attachments">
                                @foreach ($message->attachments as $attachment)
                                    <a class="ticket-attachment" href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}">
                                        <span class="ticket-attachment-icon" aria-hidden="true">↳</span>
                                        <span class="ticket-attachment-name">{{ $attachment->original_name }}<small>{{ number_format($attachment->size / 1024, 1) }} KB</small></span>
                                        <span class="ticket-attachment-download" aria-hidden="true">↓</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="ticket-conversation-empty">
                    <span aria-hidden="true">✉</span>
                    <strong>No replies yet</strong>
                    <p>Start the conversation by sending the first message below.</p>
                </div>
            @endforelse
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ route('tickets.messages.store', $ticket) }}" class="ticket-reply-form" id="ticket-reply-form">
            @csrf
            <div class="ticket-composer-heading">
                <div><span class="eyebrow">REPLY</span><h3>Write a response</h3><p>Send a message or share supporting files with this ticket.</p></div>
            </div>
            <div class="form-group ticket-composer-field">
                <label for="message-body">Your message</label>
                <textarea id="message-body" name="body" rows="5" maxlength="10000" placeholder="Write your message here...">{{ old('body') }}</textarea>
                <div class="ticket-composer-footer"><small>Be clear and include any details that help resolve the request.</small><small><span id="message-character-count">{{ mb_strlen(old('body', '')) }}</span> / 10,000</small></div>
            </div>

            <div class="form-group ticket-upload-field">
                <label for="attachments">Attachments <span class="ticket-optional-label">Optional</span></label>
                <label class="ticket-upload-zone" for="attachments" id="ticket-upload-zone">
                    <span class="ticket-upload-icon" aria-hidden="true">＋</span>
                    <span><strong>Choose files or drag them here</strong><small>PDF, JPG, PNG, TXT, CSV, DOC, DOCX, XLS, XLSX</small></span>
                    <span class="ticket-upload-browse">Browse files</span>
                </label>
                <input class="ticket-file-input" id="attachments" type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.txt,.csv,.doc,.docx,.xls,.xlsx">
                <small class="ticket-upload-help">Maximum 5 files · 10 MB per file</small>
                <div class="ticket-file-list" id="ticket-file-list" aria-live="polite"></div>
                <p class="ticket-upload-error" id="ticket-upload-error" role="alert" hidden></p>
            </div>

            @can('update', $ticket)
                <div class="ticket-internal-toggle">
                    <label><input type="checkbox" name="is_internal" value="1" id="internal-note-toggle" @checked(old('is_internal'))><span><strong>Internal note</strong><small>Only support agents and administrators can see this message.</small></span></label>
                </div>
            @endcan

            <div class="ticket-composer-actions">
                <span class="ticket-submit-hint">Your reply will be added to the ticket conversation.</span>
                <button type="submit" class="btn btn-primary" id="ticket-send-button"><span class="ticket-send-icon" aria-hidden="true">➤</span> Send reply</button>
            </div>
        </form>
        <script>
            (function () {
                var form = document.getElementById('ticket-reply-form');
                if (!form) return;

                var input = document.getElementById('attachments');
                var zone = document.getElementById('ticket-upload-zone');
                var list = document.getElementById('ticket-file-list');
                var error = document.getElementById('ticket-upload-error');
                var textarea = document.getElementById('message-body');
                var counter = document.getElementById('message-character-count');
                var sendButton = document.getElementById('ticket-send-button');
                var selectedFiles = [];
                var allowed = /\.(pdf|jpe?g|png|txt|csv|docx?|xlsx?)$/i;
                var maxSize = 10 * 1024 * 1024;

                function formatSize(bytes) {
                    return bytes >= 1024 * 1024 ? (bytes / (1024 * 1024)).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
                }

                function renderFiles() {
                    list.replaceChildren();
                    selectedFiles.forEach(function (file, index) {
                        var item = document.createElement('div');
                        item.className = 'ticket-file-item';
                        var details = document.createElement('span');
                        details.className = 'ticket-file-details';
                        var name = document.createElement('strong');
                        name.textContent = file.name;
                        var size = document.createElement('small');
                        size.textContent = formatSize(file.size);
                        details.append(name, size);
                        var remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'ticket-file-remove';
                        remove.textContent = 'Remove';
                        remove.setAttribute('aria-label', 'Remove ' + file.name);
                        remove.addEventListener('click', function () {
                            selectedFiles.splice(index, 1);
                            syncInput();
                        });
                        item.append(details, remove);
                        list.append(item);
                    });
                    zone.classList.toggle('has-files', selectedFiles.length > 0);
                }

                function syncInput() {
                    var transfer = new DataTransfer();
                    selectedFiles.forEach(function (file) { transfer.items.add(file); });
                    input.files = transfer.files;
                    renderFiles();
                }

                function addFiles(files) {
                    error.hidden = true;
                    var incoming = Array.from(files);
                    for (var i = 0; i < incoming.length; i++) {
                        var file = incoming[i];
                        if (!allowed.test(file.name)) {
                            error.textContent = 'File type not allowed: ' + file.name;
                            error.hidden = false;
                            continue;
                        }
                        if (file.size > maxSize) {
                            error.textContent = file.name + ' is larger than 10 MB.';
                            error.hidden = false;
                            continue;
                        }
                        if (selectedFiles.some(function (existing) { return existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified; })) continue;
                        if (selectedFiles.length >= 5) {
                            error.textContent = 'You can attach a maximum of 5 files.';
                            error.hidden = false;
                            break;
                        }
                        selectedFiles.push(file);
                    }
                    syncInput();
                }

                input.addEventListener('change', function () {
                    addFiles(input.files);
                });
                ['dragenter', 'dragover'].forEach(function (eventName) {
                    zone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        zone.classList.add('is-dragging');
                    });
                });
                ['dragleave', 'drop'].forEach(function (eventName) {
                    zone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        zone.classList.remove('is-dragging');
                    });
                });
                zone.addEventListener('drop', function (event) {
                    if (event.dataTransfer) addFiles(event.dataTransfer.files);
                });
                textarea.addEventListener('input', function () {
                    counter.textContent = textarea.value.length.toLocaleString();
                });
                form.addEventListener('submit', function () {
                    sendButton.disabled = true;
                    sendButton.classList.add('is-sending');
                    sendButton.textContent = 'Sending...';
                });
                renderFiles();
            })();
        </script>
    </div>

    <div class="content-card ticket-history-card ticket-detail-section">
        <div class="ticket-section-heading"><div><span class="eyebrow">AUDIT TRAIL</span><h2>Activity history</h2><p>Important changes made to this ticket.</p></div></div>

        @forelse ($ticket->histories as $history)
            @php
                $historyLabels = [
                    'status' => 'Status changed',
                    'priority' => 'Priority changed',
                    'assigned_to' => 'Agent assignment changed',
                    'team_id' => 'Team changed',
                    'project_id' => 'Project changed',
                    'estimated_minutes' => 'Time estimate updated',
                    'elapsed_minutes' => 'Tracked time updated',
                ];
                $historyValue = in_array($history->action, ['status', 'priority'], true)
                    ? fn ($value) => $value ? ucfirst(str_replace('_', ' ', $value)) : '—'
                    : fn ($value) => $value === null ? '—' : 'Updated';
            @endphp
            <article class="ticket-history-item">
                <span class="ticket-history-marker" aria-hidden="true"></span>
                <div class="ticket-history-content">
                    <div class="ticket-history-heading">
                        <strong>{{ $historyLabels[$history->action] ?? ucfirst(str_replace('_', ' ', $history->action)) }}</strong>
                        <time datetime="{{ $history->created_at->toIso8601String() }}">{{ $history->created_at->format('d.m.Y · H:i') }}</time>
                    </div>
                    @if (in_array($history->action, ['status', 'priority'], true))
                        <p><span>{{ $historyValue($history->old_value) }}</span><span class="ticket-history-arrow">→</span><strong>{{ $historyValue($history->new_value) }}</strong></p>
                    @elseif (in_array($history->action, ['estimated_minutes', 'elapsed_minutes'], true))
                        <p>{{ $history->old_value ?? '—' }} min <span class="ticket-history-arrow">→</span> <strong>{{ $history->new_value ?? '—' }} min</strong></p>
                    @elseif ($history->action === 'assigned_to')
                        <p>{{ $historyUsers->get((int) $history->old_value)?->name ?? 'Unassigned' }} <span class="ticket-history-arrow">→</span> <strong>{{ $historyUsers->get((int) $history->new_value)?->name ?? 'Unassigned' }}</strong></p>
                    @elseif ($history->action === 'team_id')
                        <p>{{ $historyTeams->get((int) $history->old_value)?->name ?? 'No team' }} <span class="ticket-history-arrow">→</span> <strong>{{ $historyTeams->get((int) $history->new_value)?->name ?? 'No team' }}</strong></p>
                    @elseif ($history->action === 'project_id')
                        <p>{{ $historyProjects->get((int) $history->old_value)?->name ?? 'No project' }} <span class="ticket-history-arrow">→</span> <strong>{{ $historyProjects->get((int) $history->new_value)?->name ?? 'No project' }}</strong></p>
                    @else
                        <p class="ticket-history-generic">The ticket details were updated.</p>
                    @endif
                    <small class="hd-person hd-history-actor">@if($history->user?->profile_photo_path)<img class="hd-avatar hd-avatar-photo hd-avatar-sm" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($history->user->profile_photo_path) }}" alt="">@else<span class="hd-avatar hd-avatar-sm">{{ strtoupper(mb_substr($history->user?->name ?? 'S',0,1)) }}</span>@endif By {{ $history->user?->name ?? 'System' }}</small>
                </div>
            </article>
        @empty
            <div class="ticket-history-empty">No activity has been recorded for this ticket yet.</div>
        @endforelse
    </div>

    @if (auth()->user()->hasRole('customer') && in_array($ticket->status, ['resolved', 'closed'], true))
        <section class="content-card ticket-detail-section">
            <span class="eyebrow">HOW DID WE DO?</span>
            <h2>Rate your support experience</h2>
            @if ($ticket->satisfactionSurvey)
                <p class="text-amber-500" style="font-size:1.5rem">{{ str_repeat('★', $ticket->satisfactionSurvey->rating) }}{{ str_repeat('☆', 5 - $ticket->satisfactionSurvey->rating) }}</p>
                <p>Thank you for your feedback. Your rating has been recorded.</p>
                @if ($ticket->satisfactionSurvey->comment)<blockquote>{{ $ticket->satisfactionSurvey->comment }}</blockquote>@endif
            @else
                <p>Your feedback helps us improve our support.</p>
                <form method="POST" action="{{ route('portal.satisfaction.store', $ticket) }}" class="mt-4">
                    @csrf
                    <div class="form-group">
                        <label for="csat-rating">Your rating</label>
                        <select id="csat-rating" name="rating" required>
                            <option value="">Choose a rating</option>
                            <option value="5">★★★★★ — Excellent</option>
                            <option value="4">★★★★☆ — Good</option>
                            <option value="3">★★★☆☆ — Okay</option>
                            <option value="2">★★☆☆☆ — Poor</option>
                            <option value="1">★☆☆☆☆ — Very poor</option>
                        </select>
                    </div>
                    <div class="form-group"><label for="csat-comment">Comment (optional)</label><textarea id="csat-comment" name="comment" rows="3" maxlength="2000" placeholder="Tell us a little more about your experience...">{{ old('comment') }}</textarea></div>
                    <button class="btn btn-primary" type="submit">Submit feedback</button>
                </form>
            @endif
        </section>
    @endif

@endsection
