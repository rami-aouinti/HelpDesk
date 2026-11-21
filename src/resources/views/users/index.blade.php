@extends('layouts.app')

@section('title', 'User management | HelpDesk')
@section('page-title', 'User management')

@section('content')
    <div class="page-header">
        <div>
            <h1>Users</h1>
            <p>Manage HelpDesk accounts, roles and access.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">+ Add user</a>
    </div>

    <livewire:users.index />
@endsection
