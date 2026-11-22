@extends('layouts.app')

@section('title', 'Automation Rules')
@section('page-title', 'Automation Rules')

@section('content')
<div class="page-header">
    <div><h1>Automation Rules</h1><p>Automate ticket assignment, prioritization, tags and notifications.</p></div>
</div>
@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
<div class="card" style="padding:24px;margin-bottom:24px">
    <h2>Create automation</h2>
    <form method="POST" action="{{ route('automations.store') }}" class="form-grid">
        @csrf
        <label>Name<input name="name" required maxlength="120" placeholder="Urgent tickets to Tier 2"></label>
        <label>Trigger<select name="trigger"><option value="ticket.created">Ticket created</option><option value="ticket.priority_changed">Priority changed</option><option value="ticket.sla_breached">SLA breached</option></select></label>
        <label class="form-full">Description<textarea name="description" rows="2"></textarea></label>
        <label>Condition logic<select name="conditions[logic]"><option value="and">All conditions (AND)</option><option value="or">Any condition (OR)</option></select></label>
        <label>Field<select name="conditions[rules][0][field]"><option value="priority">Priority</option><option value="status">Status</option><option value="team_id">Team ID</option><option value="assigned_to">Assigned agent ID</option><option value="user_id">Customer ID</option></select></label>
        <label>Operator<select name="conditions[rules][0][operator]"><option value="equals">Equals</option><option value="not_equals">Does not equal</option><option value="in">Is in list</option><option value="contains">Contains</option></select></label>
        <label>Value<input name="conditions[rules][0][value]" required placeholder="urgent"></label>
        <label>Action<select name="actions[0][type]"><option value="assign_agent">Assign agent</option><option value="set_priority">Change priority</option><option value="add_tag">Add tag</option><option value="notify_assignee">Notify assignee</option></select></label>
        <label>Action value<input name="actions[0][value]" placeholder="Agent ID / priority / tag"></label>
        <label class="form-full">Notification message (optional)<input name="actions[0][message]" maxlength="500"></label>
        <label class="form-full"><input type="checkbox" name="is_active" value="1" checked> Active</label>
        <div class="form-full"><button class="btn btn-primary" type="submit">Create rule</button></div>
    </form>
</div>
<div class="card" style="padding:24px;margin-bottom:24px">
    <h2>Rules</h2>
    @forelse ($rules as $rule)
        <div style="display:flex;gap:16px;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border-color,#ddd)">
            <div><strong>{{ $rule->name }}</strong><div>{{ $rule->trigger }} · {{ $rule->executions_count }} executions · {{ $rule->description }}</div></div>
            <div style="display:flex;gap:8px">
                <form method="POST" action="{{ route('automations.toggle', $rule) }}">@csrf @method('PATCH')<button class="btn btn-secondary">{{ $rule->is_active ? 'Disable' : 'Enable' }}</button></form>
                <form method="POST" action="{{ route('automations.destroy', $rule) }}" onsubmit="return confirm('Delete this rule?')">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form>
            </div>
        </div>
    @empty <p>No automation rules yet.</p> @endforelse
    {{ $rules->links() }}
</div>
<div class="card" style="padding:24px">
    <h2>Execution history</h2>
    <div style="overflow:auto"><table class="table"><thead><tr><th>Rule</th><th>Ticket</th><th>Trigger</th><th>Status</th><th>Started</th><th>Error</th></tr></thead><tbody>
    @forelse ($executions as $execution)
        <tr><td>{{ $execution->rule?->name ?? 'Deleted rule' }}</td><td>{{ $execution->ticket?->subject ?? '—' }}</td><td>{{ $execution->trigger }}</td><td><span class="badge">{{ $execution->status }}</span></td><td>{{ $execution->started_at?->format('Y-m-d H:i:s') }}</td><td>{{ $execution->error }}</td></tr>
    @empty <tr><td colspan="6">No executions yet.</td></tr> @endforelse
    </tbody></table></div>
</div>
@endsection
