<div>
    <div class="mb-5 grid gap-3 sm:grid-cols-2">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search tickets..." class="rounded-lg border border-slate-300 px-3 py-2">
        <select wire:model.live="status" class="rounded-lg border border-slate-300 px-3 py-2">
            <option value="">All statuses</option>
            @foreach (['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed'] as $option)
                <option value="{{ $option }}">{{ ucfirst(str_replace('_', ' ', $option)) }}</option>
            @endforeach
        </select>
    </div>

    <div class="content-card">
        @if ($tickets->isEmpty())
            <div class="empty-state">
                <h3>No tickets found</h3>
                <p>Try changing your search or create a new ticket.</p>
            </div>
        @else
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th><th>Subject</th><th>Customer</th><th>Priority</th><th>Status</th><th>Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}">
                            <td>#{{ $ticket->id }}</td>
                            <td><a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                            <td>{{ $ticket->customer?->name ?? 'Unassigned' }}</td>
                            <td>{{ ucfirst($ticket->priority) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</td>
                            <td>{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
