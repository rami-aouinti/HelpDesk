@extends('layouts.app')

@section('title', 'Tickets')
@section('page-title', 'Tickets')

@section('content')
    <div class="page-header">
        <div>
            <h1>Tickets</h1>
            <p>Manage and track customer support requests.</p>
        </div>

        @can('tickets.create')
            <a href="{{ route('tickets.create') }}" class="btn btn-primary">+ Create ticket</a>
        @endcan
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <livewire:tickets.ticket-table />
@endsection
