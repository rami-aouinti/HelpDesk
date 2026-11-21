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
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=20261121-8">
    <link rel="stylesheet" href="{{ asset('css/theme-system.css') }}?v=20261121-8">
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
            @can('viewAny', \App\Models\Project::class)
                <a href="{{ route('projects.index') }}" class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}"><span class="nav-icon">▣</span><span>Projects</span></a>
            @endcan
            @can('viewAny', \App\Models\Team::class)
                <a href="{{ route('teams.index') }}" class="nav-link {{ request()->routeIs('teams.*') ? 'active' : '' }}">
                    <span class="nav-icon">♧</span><span>Teams</span>
                </a>
            @endcan
            <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <span class="nav-icon">▤</span><span>Tickets</span>
            </a>
            @can('viewAny', \App\Models\Sprint::class)
                <a href="{{ route('sprints.index') }}" class="nav-link {{ request()->routeIs('sprints.*') ? 'active' : '' }}"><span class="nav-icon">◷</span><span>Sprints</span></a>
                @if (auth()->user()->hasRole('admin') || auth()->user()->hasRole('agent'))
                    <a href="{{ route('kanban.index') }}" class="nav-link {{ request()->routeIs('kanban.*') ? 'active' : '' }}"><span class="nav-icon">▦</span><span>Kanban</span></a>
                @endif
            @endcan
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-heading">
                <span class="breadcrumb">HelpDesk</span>
                <span class="breadcrumb-separator">/</span>
                <strong>@yield('page-title', 'Dashboard')</strong>
            </div>

            @php
                $topbarPage = match (true) {
                    request()->routeIs('users.index') => 'users',
                    request()->routeIs('teams.index') => 'teams',
                    request()->routeIs('projects.index') => 'projects',
                    request()->routeIs('tickets.index') => 'tickets',
                    request()->routeIs('sprints.index') => 'sprints',
                    default => null,
                };
            @endphp
            @if ($topbarPage)
                <div class="topbar-page-tools">
                    <label class="topbar-search" for="topbar-page-search">
                        <span aria-hidden="true">⌕</span>
                        <input id="topbar-page-search" type="search" data-search-event="{{ $topbarPage }}-search-updated" placeholder="Search {{ $topbarPage }}..." value="{{ request()->query('search', '') }}" autocomplete="off" aria-label="Search {{ $topbarPage }}">
                    </label>
                    @if ($topbarPage === 'users')
                        @can('create', \App\Models\User::class)
                            <a href="{{ route('users.create') }}" class="btn btn-primary topbar-create">+ Create user</a>
                        @endcan
                    @elseif ($topbarPage === 'teams')
                        @can('create', \App\Models\Team::class)
                            <button type="button" class="btn btn-primary topbar-create" onclick="Livewire.dispatch('teams-create')">+ Create team</button>
                        @endcan
                    @elseif ($topbarPage === 'projects')
                        @can('create', \App\Models\Project::class)
                            <button type="button" class="btn btn-primary topbar-create" onclick="Livewire.dispatch('projects-create')">+ Create project</button>
                        @endcan
                    @elseif ($topbarPage === 'tickets')
                        @can('tickets.create')
                            <a href="{{ route('tickets.create') }}" class="btn btn-primary topbar-create">+ Create ticket</a>
                        @endcan
                    @elseif ($topbarPage === 'sprints')
                        @can('create', \App\Models\Sprint::class)
                            <button type="button" class="btn btn-primary topbar-create" onclick="Livewire.dispatch('sprints-create')">+ Create sprint</button>
                        @endcan
                    @endif
                </div>
            @endif

            <div class="topbar-actions">
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Switch to dark mode" title="Switch theme">
                    <span class="theme-toggle-icon" aria-hidden="true">☾</span>
                </button>
            </div>

            <details class="account-menu">
                <summary class="account-menu-trigger">
                    @if (auth()->user()->profile_photo_path)
                        <img class="topbar-avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="Profile photo">
                    @else
                        <span class="topbar-avatar avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                    @endif
                </summary>
                <div class="account-menu-panel">
                    <div class="account-menu-user">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->email }}</small>
                    </div>
                    <div class="account-menu-divider"></div>
                    <a href="{{ route('profile.edit') }}" class="account-menu-item">My profile</a>
                    <a href="{{ route('appearance.edit') }}" class="account-menu-item">Appearance &amp; themes</a>
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
        var search = document.getElementById('topbar-page-search');
        if (!search) return;
        var debounce;
        search.addEventListener('input', function () {
            window.clearTimeout(debounce);
            var value = search.value;
            var eventName = search.dataset.searchEvent;
            debounce = window.setTimeout(function () {
                if (window.Livewire && eventName) window.Livewire.dispatch(eventName, { search: value });
            }, 300);
        });
    })();
</script>


<script>
    (function () {
        var accents = ['indigo', 'blue', 'emerald', 'rose', 'amber', 'violet', 'cyan'];
        var storageKey = 'helpdesk-accent';

        function applyAccent(value) {
            var accent = accents.indexOf(value) !== -1 ? value : 'indigo';
            document.documentElement.setAttribute('data-accent', accent);
            document.querySelectorAll('[data-accent-option]').forEach(function (button) {
                button.setAttribute('aria-pressed', String(button.getAttribute('data-accent-option') === accent));
            });
        }

        try {
            applyAccent(localStorage.getItem(storageKey) || 'indigo');
        } catch (error) {
            applyAccent('indigo');
        }

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-accent-option]');
            if (!button) return;

            var accent = button.getAttribute('data-accent-option');
            if (accents.indexOf(accent) === -1) return;

            try {
                localStorage.setItem(storageKey, accent);
            } catch (error) {
                // The selected palette still applies for the current page.
            }
            applyAccent(accent);
        });
    })();
</script>

</body>
</html>
