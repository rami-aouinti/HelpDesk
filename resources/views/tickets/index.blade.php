@extends('layouts.app')

@section('title', 'Tickets')
@section('page-title', 'Tickets')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <livewire:tickets.ticket-table />
@endsection
