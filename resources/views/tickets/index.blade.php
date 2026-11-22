@extends('layouts.app')

@section('title', 'Tickets')
@section('meta-description', 'Browse and manage support tickets, review their status and priority, and keep customer requests moving toward resolution.')
@section('page-title', 'Tickets')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <livewire:tickets.ticket-table />

    <dialog class="hd-ticket-dialog" id="ticket-create-dialog" aria-labelledby="ticket-create-title">
        <div class="hd-modal-heading">
            <div><span class="hd-eyebrow">SUPPORT WORKSPACE</span><h2 id="ticket-create-title">Create a ticket</h2><p>Describe the request and give the support team the context they need.</p></div>
            <button type="button" class="hd-modal-close" aria-label="Close" onclick="document.getElementById('ticket-create-dialog').close()">×</button>
        </div>
        @if ($errors->any())
            <div class="alert alert-error"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('tickets.store') }}">
            @csrf
            <div class="form-group"><label for="ticket-create-subject">Subject</label><input id="ticket-create-subject" name="subject" type="text" value="{{ old('subject') }}" maxlength="255" placeholder="Example: Cannot access my account" required></div>
            <div class="form-group"><label for="ticket-create-description">Description</label><textarea id="ticket-create-description" name="description" rows="6" placeholder="Describe the problem, what you expected and what happened..." required>{{ old('description') }}</textarea></div>
            @if ($projects->isNotEmpty())
                <div class="form-group"><label for="ticket-create-project">Project <span class="ticket-optional-label">Optional</span></label><select id="ticket-create-project" name="project_id"><option value="">No project</option>@foreach ($projects as $project)<option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->name }}</option>@endforeach</select></div>
            @endif
            <div class="form-group"><label for="ticket-create-priority">Priority</label><select id="ticket-create-priority" name="priority" required>@foreach (['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" @selected(old('priority','normal') === $priority)>{{ ucfirst($priority) }}</option>@endforeach</select></div>
            <div class="hd-ticket-dialog-actions"><button type="button" class="btn btn-secondary" onclick="document.getElementById('ticket-create-dialog').close()">Cancel</button><button type="submit" class="btn btn-primary">Create ticket</button></div>
        </form>
    </dialog>
    @if ($errors->any())
        <script>document.addEventListener('DOMContentLoaded', function () { document.getElementById('ticket-create-dialog')?.showModal(); });</script>
    @endif
@endsection
