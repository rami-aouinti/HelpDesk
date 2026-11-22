<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'HelpDesk | Support Workspace')</title>
    <meta name="description" content="@yield('meta-description', 'Access your HelpDesk account to manage support requests and stay connected with your team.')">
    <meta name="application-name" content="HelpDesk">
    <meta name="theme-color" content="#111827">
    <link rel="icon" type="image/png" href="{{ asset('helpdesk.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('helpdesk.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6 py-12">
        <a href="{{ url('/') }}" class="guest-brand mb-8 flex items-center justify-center gap-3 text-3xl font-bold tracking-tight text-slate-900" aria-label="HelpDesk home"><img src="{{ asset('logo.png') }}" alt="" width="48" height="48"> <span>HelpDesk</span></a>
        <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            @yield('content')
        </section>
    </main>
    @livewireScripts
</body>
</html>
