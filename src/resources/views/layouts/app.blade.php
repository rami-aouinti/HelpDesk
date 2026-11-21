<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HelpDesk')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <span class="brand-icon">H</span>
            <span>HelpDesk</span>
        </a>

        <div class="nav-label">WORKSPACE</div>
        <nav>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">Tickets</a>
            <a href="{{ route('help') }}" class="nav-link {{ request()->routeIs('help') ? 'active' : '' }}">Help center</a>
            @can('viewAny', \App\Models\User::class)
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">Users</a>
            @endcan
            @can('viewAny', \App\Models\Team::class)
                <a href="{{ route('teams.index') }}" class="nav-link {{ request()->routeIs('teams.*') ? 'active' : '' }}">Teams</a>
            @endcan
        </nav>

        <div class="sidebar-bottom">
            <div class="nav-label">ACCOUNT</div>
            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">My profile</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link">Log out</button>
            </form>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <span class="breadcrumb">Workspace /</span>
                <strong>@yield('page-title', 'Dashboard')</strong>
            </div>
            <a href="{{ route('profile.edit') }}" class="user-profile" style="text-decoration:none;color:inherit;">
                @if (auth()->user()->profile_photo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}"
                         alt="Profile photo"
                         style="width:36px;height:36px;object-fit:cover;border-radius:50%;">
                @else
                    <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                @endif
                <span>{{ auth()->user()->name }}</span>
            </a>
        </header>

        <section class="page-content">
            @yield('content')
        </section>
    </main>
</div>
@livewireScripts
</body>
</html>
