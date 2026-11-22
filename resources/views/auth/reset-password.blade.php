@extends('layouts.guest')

@section('title', 'Choose a new password | HelpDesk')
@section('meta-description', 'Set a new password for your HelpDesk account and restore secure access to your workspace.')

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Choose a new password</h1>
    @if ($errors->any()) <div class="mb-4 text-sm text-red-700">{{ $errors->first() }}</div> @endif
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <label class="block text-sm font-medium">Email
            <input name="email" type="email" required value="{{ old('email', $request->email) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label class="block text-sm font-medium">New password
            <input name="password" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label class="block text-sm font-medium">Confirm password
            <input name="password_confirmation" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-white">Update password</button>
    </form>
@endsection
