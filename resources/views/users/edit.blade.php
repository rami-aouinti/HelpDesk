@extends('layouts.app')

@section('title', 'Edit user | HelpDesk')
@section('page-title', 'Edit user')

@section('content')
    <div class="page-header">
        <div><h1>Edit user</h1><p>Update account details and role for {{ $managedUser->name }}.</p></div>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Back to users</a>
    </div>
    @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <section class="content-card form-card">
        <form method="POST" action="{{ route('users.update', $managedUser) }}">
            @csrf
            @method('PUT')
            @include('users.partials.form', ['submitLabel' => 'Save changes', 'managedUser' => $managedUser])
        </form>
    </section>
@endsection
