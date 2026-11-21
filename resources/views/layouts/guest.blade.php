<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'HelpDesk' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6 py-12">
        <a href="{{ url('/') }}" class="mb-8 text-center text-3xl font-bold tracking-tight text-slate-900">HelpDesk</a>
        <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            @yield('content')
        </section>
    </main>
    @livewireScripts
</body>
</html>
