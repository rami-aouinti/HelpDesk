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
        <section id="knowledge-suggestions" class="alert" hidden aria-live="polite" style="margin-bottom:18px">
            <strong>Suggested help articles</strong><p>These articles may answer your question before you submit a ticket.</p>
            <div id="knowledge-suggestion-results"></div>
        </section>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const subject = document.getElementById('subject');
    const description = document.getElementById('description');
    const panel = document.getElementById('knowledge-suggestions');
    const results = document.getElementById('knowledge-suggestion-results');
    let timer;
    let controller;
    const searchArticles = async () => {
        const query = [subject.value, description.value].join(' ').trim();
        if (query.replace(/[^a-z0-9]/gi, '').length < 3) { panel.hidden = true; return; }
        controller?.abort();
        controller = new AbortController();
        try {
            const response = await fetch(`{{ route('knowledge-base.suggestions') }}?query=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal
            });
            if (!response.ok) return;
            const payload = await response.json();
            results.replaceChildren();
            payload.data.forEach((article) => {
                const link = document.createElement('a');
                link.href = article.url;
                link.textContent = article.title;
                link.style.display = 'block';
                link.style.fontWeight = '600';
                const summary = document.createElement('p');
                summary.textContent = article.excerpt;
                summary.style.margin = '4px 0 12px';
                results.append(link, summary);
            });
            panel.hidden = payload.data.length === 0;
        } catch (error) {
            if (error.name !== 'AbortError') console.error('Knowledge suggestions failed.', error);
        }
    };
    [subject, description].forEach((field) => field.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(searchArticles, 450);
    }));
});
</script>
@endsection
