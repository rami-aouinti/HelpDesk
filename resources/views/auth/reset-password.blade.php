@extends('layouts.guest')

@section('title', 'Choose a new password | HelpDesk')
@section('meta-description', 'Set a new password for your HelpDesk account and restore secure access to your workspace.')

@section('content')
    <span class="auth-eyebrow">SECURE YOUR ACCOUNT</span>
    <h1 class="auth-title">Choose a new<br>password.</h1>
    <p class="auth-subtitle">Create a new password to get back into your HelpDesk workspace.</p>
    @if ($errors->any())
        <div class="auth-alert auth-alert-error" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <label for="email" class="auth-field"><span class="auth-label">Email address</span>
            <input id="email" name="email" type="email" required value="{{ old('email', $request->email) }}" autocomplete="email" class="auth-input">
        </label>
        <label for="password" class="auth-field"><span class="auth-label">New password</span>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Enter a new password" class="auth-input">
        </label>
        <label for="password_confirmation" class="auth-field"><span class="auth-label">Confirm new password</span>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Repeat your new password" class="auth-input">
        </label>
        <button class="auth-primary mt-2" type="submit">Update password <span aria-hidden="true">→</span></button>
    </form>
@endsection
