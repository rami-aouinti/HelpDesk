@extends('layouts.app')

@section('title', 'Dashboard | HelpDesk')
@section('page-title', 'Dashboard')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Good morning 👋</h1>
            <p>Here's what's happening with your support desk today.</p>
        </div>

        @can('tickets.create')
        <a href="{{ route('tickets.create') }}" class="button-primary">+ Create ticket</a>
        @endcan
    </div>

    <div class="stats-grid">
        @foreach ([
            ['label' => 'Total tickets', 'value' => $statistics['total']],
            ['label' => 'Open tickets', 'value' => $statistics['open']],
            ['label' => 'In progress', 'value' => $statistics['in_progress']],
            ['label' => 'Resolved', 'value' => $statistics['resolved']],
        ] as $stat)
            <article class="stat-card">
                <span>{{ $stat['label'] }}</span>
                <strong>{{ $stat['value'] }}</strong>
            </article>
        @endforeach
    </div>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Recent tickets</h2>
                <p>Latest customer support requests</p>
            </div>
        </div>

        @if ($recentTickets->isEmpty())
            <div class="empty-state">
                <h3>No tickets yet</h3>
                <p>Customer requests will appear here when they are created.</p>
            </div>
        @else
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Customer</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($recentTickets as $ticket)
                        <tr>
                            <td>
                                <strong>{{ $ticket->subject }}</strong>
                                <small>#{{ $ticket->id }}</small>
                            </td>
                            <td>{{ $ticket->customer?->name ?? 'Unassigned' }}</td>
                            <td>{{ ucfirst($ticket->priority) }}</td>
                            <td>
                                    <span class="status status-{{ $ticket->status }}">
                                        {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                                    </span>
                            </td>
                            <td>{{ $ticket->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
