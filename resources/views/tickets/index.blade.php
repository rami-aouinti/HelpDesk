@extends('layouts.app')

@section('title', 'Tickets')
@section('meta-description', 'Browse and manage support tickets, review their status and priority, and keep customer requests moving toward resolution.')
@section('page-title', 'Tickets')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <livewire:tickets.ticket-table />
@endsection
