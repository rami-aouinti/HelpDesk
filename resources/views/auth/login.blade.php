@extends('layouts.guest')

@section('title', 'Sign in | HelpDesk')
@section('meta-description', 'Sign in to your HelpDesk workspace to manage support tickets, collaborate with your team and follow customer requests.')

@section('content')
    <span class="auth-eyebrow">WELCOME BACK</span>
    <h1 class="auth-title">Good to see you.</h1>
    <p class="auth-subtitle">Sign in to your workspace and pick up right where you left off.</p>

    @if (session('status'))
        <div class="auth-alert auth-alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="auth-alert auth-alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="email" class="auth-field"><span class="auth-label">Email address</span>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@company.com" class="auth-input">
        </label>
        <label for="password" class="auth-field"><span class="auth-label">Password</span>
            <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Enter your password" class="auth-input">
        </label>
        <div class="mb-6 flex items-center justify-between gap-3 text-sm">
            <label class="flex items-center gap-2 text-slate-500"><input type="checkbox" name="remember" class="auth-check"> Remember me</label>
            <a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a>
        </div>
        <button class="auth-primary" type="submit">Sign in to HelpDesk <span aria-hidden="true">→</span></button>
    </form>
    <p class="mt-7 text-center text-sm text-slate-500">New to HelpDesk? <a class="auth-link" href="{{ route('register') }}">Create an account</a></p>
@endsection
