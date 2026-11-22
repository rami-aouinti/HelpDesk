@extends('layouts.guest')

@section('title', 'Create an account | HelpDesk')
@section('meta-description', 'Create a HelpDesk account to submit support requests, track progress and collaborate with your support team.')

@section('content')
    <span class="auth-eyebrow">GET STARTED</span>
    <h1 class="auth-title">Your workspace<br>starts here.</h1>
    <p class="auth-subtitle">Create your account and bring your team’s support work together.</p>

    @if ($errors->any())
        <div class="auth-alert auth-alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <label for="name" class="auth-field"><span class="auth-label">Full name</span>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" placeholder="Your name" class="auth-input">
        </label>
        <label for="email" class="auth-field"><span class="auth-label">Work email</span>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@company.com" class="auth-input">
        </label>
        <label for="password" class="auth-field"><span class="auth-label">Password</span>
            <input id="password" name="password" type="password" required autocomplete="new-password" placeholder="Create a strong password" class="auth-input">
        </label>
        <label for="password_confirmation" class="auth-field"><span class="auth-label">Confirm password</span>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Enter your password again" class="auth-input">
        </label>
        <button class="auth-primary mt-2" type="submit">Create my account <span aria-hidden="true">→</span></button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-500">Already have an account? <a class="auth-link" href="{{ route('login') }}">Sign in</a></p>
@endsection
