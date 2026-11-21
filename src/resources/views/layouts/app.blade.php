<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HelpDesk')</title>
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('helpdesk-theme');
                if (!theme) theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (error) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand" aria-label="HelpDesk dashboard">
            <span class="brand-icon">H</span>
            <span>HelpDesk</span>
        </a>

        <div class="nav-label">WORKSPACE</div>
        <nav class="sidebar-nav">
            @can('viewAny', \App\Models\User::class)
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <span class="nav-icon">♙</span><span>Users</span>
                </a>
            @endcan
            @can('viewAny', \App\Models\Team::class)
                <a href="{{ route('teams.index') }}" class="nav-link {{ request()->routeIs('teams.*') ? 'active' : '' }}">
                    <span class="nav-icon">♧</span><span>Teams</span>
                </a>
            @endcan
            <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <span class="nav-icon">▤</span><span>Tickets</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-heading">
                <span class="breadcrumb">HelpDesk</span>
                <span class="breadcrumb-separator">/</span>
                <strong>@yield('page-title', 'Dashboard')</strong>
            </div>

            <div class="topbar-actions">
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Switch to dark mode" title="Switch theme">
                    <span class="theme-toggle-icon" aria-hidden="true">☾</span>
                    <span class="theme-toggle-label">Dark mode</span>
                </button>
            </div>

            <details class="account-menu">
                <summary class="account-menu-trigger">
                    @if (auth()->user()->profile_photo_path)
                        <img class="topbar-avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="Profile photo">
                    @else
                        <span class="topbar-avatar avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    @endif
                    <span class="account-menu-name">{{ auth()->user()->name }}</span>
                    <span class="account-menu-chevron">⌄</span>
                </summary>
                <div class="account-menu-panel">
                    <div class="account-menu-user">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->email }}</small>
                    </div>
                    <div class="account-menu-divider"></div>
                    <a href="{{ route('profile.edit') }}" class="account-menu-item">My profile</a>
                    <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" class="quick-photo-form">
                        @csrf
                        <label for="quick-profile-photo" class="account-menu-item quick-photo-label">Change photo</label>
                        <input id="quick-profile-photo" type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" required>
                        <button type="submit" class="quick-photo-submit">Upload photo</button>
                    </form>
                    <a href="{{ route('help') }}" class="account-menu-item">Help center</a>
                    <div class="account-menu-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="account-menu-item account-menu-logout">Log out</button>
                    </form>
                </div>
            </details>
        </header>

        <section class="page-content">
            @yield('content')
        </section>
    </main>
</div>
@livewireScripts
<script>
    (function () {
        var button = document.getElementById('theme-toggle');
        if (!button) return;
        function syncThemeButton() {
            var dark = document.documentElement.getAttribute('data-theme') === 'dark';
            var icon = button.querySelector('.theme-toggle-icon');
            var label = button.querySelector('.theme-toggle-label');
            button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
            button.setAttribute('title', dark ? 'Switch to light mode' : 'Switch to dark mode');
            if (icon) icon.textContent = dark ? '☀' : '☾';
            if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
        }
        syncThemeButton();
        button.addEventListener('click', function () {
            var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('helpdesk-theme', next); } catch (error) {}
            syncThemeButton();
        });
    })();
</script>
</body>
</html>
