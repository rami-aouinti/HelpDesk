@extends('layouts.guest')

@section('title', 'Sign in | HelpDesk')
@section('meta-description', 'Sign in to your HelpDesk workspace to manage support tickets, collaborate with your team and follow customer requests.')

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Welcome back</h1>
    <p class="mb-6 text-sm text-slate-500">Sign in to your HelpDesk account.</p>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember"> Remember me</label>
        <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-medium text-white">Log in</button>
    </form>

    <div class="mt-5 flex justify-between text-sm">
        <a class="text-blue-700 hover:underline" href="{{ route('password.request') }}">Forgot password?</a>
        <a class="text-blue-700 hover:underline" href="{{ route('register') }}">Create account</a>
    </div>
@endsection
