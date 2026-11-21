@extends('layouts.app')

@section('title', 'Tickets')

@section('content')
    <div class="page-header">
        <div>
            <h1>Tickets</h1>
            <p>Manage and track customer support requests.</p>
        </div>

        <a href="{{ route('tickets.create') }}" class="btn btn-primary">
            + Create ticket
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="content-card">
        @if ($tickets->isEmpty())
            <div class="empty-state">
                <h3>No tickets yet</h3>
                <p>Create your first support ticket.</p>
                <a href="{{ route('tickets.create') }}" class="btn btn-primary">
                    Create ticket
                </a>
            </div>
        @else
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($tickets as $ticket)
                        <tr>
                            <td>#{{ $ticket->id }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td>{{ $ticket->customer?->name ?? 'Unassigned' }}</td>
                            <td>{{ ucfirst($ticket->priority) }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</td>
                            <td>{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{ $tickets->links() }}
        @endif
    </div>
@endsection
