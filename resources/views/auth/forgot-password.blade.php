@extends('layouts.guest')

@section('title', 'Password recovery | HelpDesk')
@section('meta-description', 'Request a secure password reset link to regain access to your HelpDesk account.')

@section('content')
    <span class="auth-eyebrow">ACCOUNT RECOVERY</span>
    <h1 class="auth-title">Forgot your<br>password?</h1>
    <p class="auth-subtitle">It happens. Enter the email address linked to your account and we’ll send you a secure reset link.</p>

    @if (session('status'))
        <div class="auth-alert auth-alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="auth-alert auth-alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email" class="auth-field"><span class="auth-label">Email address</span>
            <input id="email" name="email" type="email" required value="{{ old('email') }}" autocomplete="email" placeholder="you@company.com" class="auth-input">
        </label>
        <button class="auth-primary mt-2" type="submit">Send me a reset link <span aria-hidden="true">→</span></button>
    </form>
    <p class="mt-7 text-center text-sm"><a class="auth-link" href="{{ route('login') }}">← Back to sign in</a></p>
@endsection
