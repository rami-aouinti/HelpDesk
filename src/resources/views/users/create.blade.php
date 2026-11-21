@extends('layouts.app')

@section('title', 'Create user | HelpDesk')
@section('page-title', 'Create user')

@section('content')
    <div class="page-header">
        <div><h1>Create user</h1><p>Add a new administrator, agent or customer.</p></div>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Back to users</a>
    </div>
    @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
    <section class="content-card form-card">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            @include('users.partials.form', ['submitLabel' => 'Create user', 'managedUser' => null])
        </form>
    </section>
@endsection
