@extends('layouts.guest')

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Verify your email</h1>
    <p class="mb-6 text-sm text-slate-600">We sent you a verification link. Open it to activate your HelpDesk account.</p>
    @if (session('status') === 'verification-link-sent')
        <p class="mb-4 text-sm text-emerald-700">A new verification link has been sent.</p>
    @endif
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-white">Resend verification email</button>
    </form>
    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button class="text-sm text-blue-700 hover:underline">Log out</button>
    </form>
@endsection
