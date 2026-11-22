@extends('layouts.app')

@section('title', 'Notification deliveries | HelpDesk')
@section('page-title', 'Notification deliveries')

@section('content')
<div class="page-header">
    <div>
        <span class="eyebrow">COMMUNICATIONS · OPERATIONS</span>
        <h1>Notification deliveries</h1>
        <p>Monitor delivery attempts across email and in-app channels.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4" style="margin-bottom:24px">
    @foreach ([
        ['label' => 'Total deliveries', 'value' => $summary['total']],
        ['label' => 'Pending', 'value' => $summary['pending']],
        ['label' => 'Sent', 'value' => $summary['sent']],
        ['label' => 'Failed', 'value' => $summary['failed']],
    ] as $metric)
        <article class="card" style="padding:20px">
            <p style="margin:0 0 8px;color:var(--text-muted)">{{ $metric['label'] }}</p>
            <strong style="font-size:28px;line-height:1.2">{{ number_format($metric['value']) }}</strong>
        </article>
    @endforeach
</div>

<section class="card" style="padding:20px;margin-bottom:20px">
    <form method="GET" action="{{ route('admin.communications.deliveries.index') }}" class="form-grid">
        <label>Search
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Recipient, notification ID, error…">
        </label>
        <label>Status
            <select name="status">
                <option value="">All statuses</option>
                @foreach (['pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>Channel
            <select name="channel">
                <option value="">All channels</option>
                @foreach (['mail' => 'Email', 'database' => 'In-app', 'broadcast' => 'Broadcast'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['channel'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>From<input type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
        <label>To<input type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
        <div style="display:flex;gap:8px;align-items:end">
            <button class="btn btn-primary" type="submit">Apply filters</button>
            <a class="btn btn-secondary" href="{{ route('admin.communications.deliveries.index') }}">Reset</a>
        </div>
    </form>
</section>

<section class="card" style="padding:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px">
        <div><h2 style="margin:0">Delivery history</h2><p style="margin:4px 0 0;color:var(--text-muted)">{{ $deliveries->total() }} records found</p></div>
    </div>
    <div style="overflow-x:auto">
        <table class="table">
            <thead><tr><th>Recipient</th><th>Notification</th><th>Channel</th><th>Status</th><th>Attempts</th><th>Last attempt</th><th>Details</th></tr></thead>
            <tbody>
                @forelse ($deliveries as $delivery)
                    <tr>
                        <td>
                            <strong>{{ $delivery->recipient_name ?: class_basename($delivery->notifiable_type) . ' #' . $delivery->notifiable_id }}</strong>
                            @if ($delivery->recipient_email)<div style="font-size:12px;color:var(--text-muted)">{{ $delivery->recipient_email }}</div>@endif
                        </td>
                        <td><strong>{{ class_basename($delivery->notification_type) }}</strong><div style="font-size:12px;color:var(--text-muted)">#{{ $delivery->notification_id }}</div></td>
                        <td>{{ match ($delivery->channel) { 'mail' => 'Email', 'database' => 'In-app', default => ucfirst($delivery->channel) } }}</td>
                        <td><span class="badge">{{ ucfirst($delivery->status) }}</span></td>
                        <td>{{ $delivery->attempts }}</td>
                        <td>{{ $delivery->last_attempt_at ? \Illuminate\Support\Carbon::parse($delivery->last_attempt_at)->format('d M Y H:i') : '—' }}</td>
                        <td>
                            @if ($delivery->last_error)
                                <details><summary style="cursor:pointer">View error</summary><pre style="white-space:pre-wrap;max-width:360px;font-size:12px">{{ $delivery->last_error }}</pre></details>
                            @else
                                <span style="color:var(--text-muted)">{{ $delivery->sent_at ? 'Sent ' . \Illuminate\Support\Carbon::parse($delivery->sent_at)->diffForHumans() : '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:36px;color:var(--text-muted)">No delivery records match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:18px">{{ $deliveries->links() }}</div>
</section>
@endsection
