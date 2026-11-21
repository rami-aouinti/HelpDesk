@extends('layouts.app')

@section('title', 'User management | HelpDesk')
@section('page-title', 'User management')

@section('content')
    @can('create', \App\Models\User::class)
        <div class="page-actions">
            <a href="{{ route('users.create') }}" class="btn btn-primary">+ Add user</a>
        </div>
    @endcan

    <livewire:users.index />
@endsection
