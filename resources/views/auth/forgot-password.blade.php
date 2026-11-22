@extends('layouts.guest')

@section('title', 'Password recovery | HelpDesk')
@section('meta-description', 'Request a secure password reset link to regain access to your HelpDesk account.')

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Reset your password</h1>
    <p class="mb-6 text-sm text-slate-500">We'll email you a password reset link.</p>
    @if (session('status')) <div class="mb-4 text-sm text-emerald-700">{{ session('status') }}</div> @endif
    @if ($errors->any()) <div class="mb-4 text-sm text-red-700">{{ $errors->first() }}</div> @endif
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <label class="block text-sm font-medium">Email
            <input name="email" type="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-white">Send reset link</button>
    </form>
@endsection
