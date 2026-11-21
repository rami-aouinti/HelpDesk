@extends('layouts.app')

@section('title', 'Tickets')
@section('page-title', 'Tickets')

@section('content')
    @can('tickets.create')
        <div class="page-actions">
            <a href="{{ route('tickets.create') }}" class="btn btn-primary">+ Create ticket</a>
        </div>
    @endcan

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <livewire:tickets.ticket-table />
@endsection
