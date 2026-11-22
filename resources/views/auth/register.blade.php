@extends('layouts.guest')

@section('title', 'Create an account | HelpDesk')
@section('meta-description', 'Create a HelpDesk account to submit support requests, track progress and collaborate with your support team.')

@section('content')
    <h1 class="mb-2 text-2xl font-semibold">Create your account</h1>
    <p class="mb-6 text-sm text-slate-500">Get started with HelpDesk.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
        @csrf
        <div>
            <label for="name" class="mb-1 block text-sm font-medium">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </div>
        <button class="w-full rounded-lg bg-slate-900 px-4 py-2.5 font-medium text-white">Create account</button>
    </form>

    <p class="mt-5 text-center text-sm text-slate-600">Already registered? <a class="text-blue-700 hover:underline" href="{{ route('login') }}">Log in</a></p>
@endsection
