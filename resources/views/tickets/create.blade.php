@extends('layouts.app')

@section('title', 'Create Ticket')
@section('meta-description', 'Submit a support ticket with the details your team needs to understand, prioritize and resolve your request.')

@section('content')
    <div class="page-header">
        <div>
            <h1>Create a ticket</h1>
            <p>Describe your issue and submit a new support request.</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="btn btn-secondary">
            Back to tickets
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="content-card">
        <form method="POST" action="{{ route('tickets.store') }}">
            @csrf

            <div class="form-group">
                <label for="subject">Subject</label>
                <input
                    id="subject"
                    name="subject"
                    type="text"
                    value="{{ old('subject') }}"
                    placeholder="Example: Cannot access my account"
                    required
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    placeholder="Describe your problem..."
                    required
                >{{ old('description') }}</textarea>
            </div>

            @if ($projects->isNotEmpty())
                <div class="form-group"><label for="project_id">Project (optional)</label><select id="project_id" name="project_id"><option value="">No project</option>@foreach ($projects as $project)<option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>{{ $project->name }}</option>@endforeach</select></div>
            @endif

            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority" required>
                    @foreach (['low', 'normal', 'high', 'urgent'] as $priority)
                        <option
                            value="{{ $priority }}"
                            @selected(old('priority', 'normal') === $priority)
                        >
                            {{ ucfirst($priority) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                Create ticket
            </button>
        </form>
    </div>
@endsection
