<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HelpDesk | Support Workspace')</title>
    <meta name="description" content="@yield('meta-description', 'HelpDesk brings your support workspace together. Manage tickets, teams, projects and customer conversations in one place.')">
    <meta name="application-name" content="HelpDesk">
    <meta name="theme-color" content="#111827">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="HelpDesk">
    <meta property="og:title" content="@yield('title', 'HelpDesk | Support Workspace')">
    <meta property="og:description" content="@yield('meta-description', 'Manage support tickets, teams and projects with HelpDesk.')">
    <link rel="icon" type="image/png" href="{{ asset('helpdesk.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('helpdesk.png') }}">
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
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=20261122-4">
    <link rel="stylesheet" href="{{ asset('css/theme-system.css') }}?v=20261121-10">
    <link rel="stylesheet" href="{{ asset('css/admin-experience.css') }}?v=20261122-3">
    @livewireStyles
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand" aria-label="HelpDesk dashboard">
            <img class="brand-logo" src="{{ asset('logo.png') }}" alt="HelpDesk logo" width="42" height="42">
            <span>HelpDesk</span>
        </a>

        <div class="nav-label">WORKSPACE</div>
        <nav class="sidebar-nav">
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

        <div class="sidebar-bottom">
            <div class="nav-label">ADMINISTRATION</div>
            <nav class="sidebar-nav sidebar-admin-nav" aria-label="Administration">
                @can('reports.view')
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"><span class="nav-icon">▥</span><span>Reports &amp; Analytics</span></a>
                @endcan
                @can('viewAny', \App\Models\User::class)
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <span class="nav-icon">♙</span><span>Users</span>
                    </a>
                @endcan
                <a href="{{ route('appearance.edit') }}" class="nav-link {{ request()->routeIs('appearance.*') ? 'active' : '' }}">
                    <span class="nav-icon">⚙</span><span>Settings</span>
                </a>
            </nav>
        </div>
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
                    request()->routeIs('kanban.index') => 'kanban',
                    default => null,
                };
            @endphp
            @if ($topbarPage === 'kanban')
                @php
                    $kanbanSprints = \App\Models\Sprint::query()
                        ->orderByDesc('start_date')
                        ->orderByDesc('id')
                        ->get(['id', 'name']);
                    $selectedKanbanSprint = (int) request()->query('sprint', $kanbanSprints->first()?->id ?? 0);
                @endphp
                <div class="topbar-page-tools kanban-topbar-tools">
                    <label class="kanban-topbar-select" for="kanban-topbar-sprint">
                        <span class="sr-only">Select sprint</span>
                        <select id="kanban-topbar-sprint" aria-label="Select sprint" onchange="Livewire.dispatch('kanban-select-sprint', { sprintId: Number(this.value) })" @disabled($kanbanSprints->isEmpty())>
                            @forelse ($kanbanSprints as $kanbanOption)
                                <option value="{{ $kanbanOption->id }}" @selected($selectedKanbanSprint === $kanbanOption->id)>{{ $kanbanOption->name }}</option>
                            @empty
                                <option value="">No sprints</option>
                            @endforelse
                        </select>
                    </label>
                    <label class="kanban-topbar-filter">
                        <input type="checkbox" aria-label="Only my assigned tickets" @checked(request()->boolean('onlyMine')) onchange="Livewire.dispatch('kanban-toggle-mine', { onlyMine: this.checked })" @disabled($kanbanSprints->isEmpty())>
                        <span>Only my assigned tickets</span>
                    </label>
                    <a href="{{ route('sprints.index') }}" class="btn btn-secondary">Manage sprints</a>
                </div>
            @elseif ($topbarPage)
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
                            <button type="button" class="btn btn-primary topbar-create" onclick="document.getElementById('ticket-create-dialog')?.showModal()">+ Create ticket</button>
                        @endcan
                    @elseif ($topbarPage === 'sprints')
                        @can('create', \App\Models\Sprint::class)
                            <button type="button" class="btn btn-primary topbar-create" onclick="Livewire.dispatch('sprints-create')">+ Create sprint</button>
                        @endcan
                    @endif
                </div>
            @endif

            <div class="topbar-right">
            <div class="topbar-actions">
                <details class="notification-menu" id="notification-menu"
                    data-feed-url="{{ route('notifications.recent') }}"
                    data-read-url="{{ route('notifications.read', ['notification' => '__ID__']) }}"
                    data-read-all-url="{{ route('notifications.read-all') }}">
                    <summary class="notification-trigger" aria-label="Open notifications">
                        <span aria-hidden="true">♧</span>
                        <span class="notification-count" id="notification-count" hidden>0</span>
                    </summary>
                    <div class="notification-panel">
                        <div class="notification-panel-header">
                            <div><strong>Notifications</strong><small id="notification-summary">Your latest updates</small></div>
                            <button type="button" id="notification-read-all" class="notification-read-all" hidden>Mark all read</button>
                        </div>
                        <div class="notification-list" id="notification-list" aria-live="polite">
                            <div class="notification-loading">Loading notifications…</div>
                        </div>
                        <a class="notification-view-all" href="{{ route('tickets.index') }}">Go to tickets</a>
                    </div>
                </details>
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
            </div>
        </header>

        <section class="page-content{{ request()->routeIs('projects.index') ? ' page-content-projects' : '' }}{{ request()->routeIs('profile.edit') ? ' page-content-profile' : '' }}">
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

<script>
    (function () {
        var menu = document.getElementById('notification-menu');
        if (!menu) return;

        var list = document.getElementById('notification-list');
        var count = document.getElementById('notification-count');
        var summary = document.getElementById('notification-summary');
        var readAll = document.getElementById('notification-read-all');
        var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        var feedUrl = menu.dataset.feedUrl;

        function element(tag, className, value) {
            var node = document.createElement(tag);
            if (className) node.className = className;
            if (value !== undefined) node.textContent = value;
            return node;
        }

        function updateCount(value) {
            count.textContent = value > 99 ? '99+' : String(value);
            count.hidden = value < 1;
            readAll.hidden = value < 1;
            summary.textContent = value ? value + ' unread update' + (value === 1 ? '' : 's') : 'You are all caught up';
        }

        function render(items) {
            list.replaceChildren();
            if (!items.length) {
                list.append(element('div', 'notification-empty', 'No notifications yet. Updates about your tickets will appear here.'));
                return;
            }

            items.forEach(function (item) {
                var link = element('a', 'notification-item' + (item.read_at ? '' : ' is-unread'));
                link.href = item.url || '{{ route('tickets.index') }}';
                var dot = element('span', 'notification-dot');
                var content = element('span', 'notification-content');
                content.append(element('strong', '', item.title || 'HelpDesk update'));
                content.append(element('span', 'notification-message', item.message || ''));
                if (item.ticket_subject) content.append(element('span', 'notification-ticket', item.ticket_subject));
                content.append(element('small', 'notification-time', item.created_at || ''));
                link.append(dot, content);
                link.addEventListener('click', function () {
                    if (!item.read_at) {
                        fetch(menu.dataset.readUrl.replace('__ID__', encodeURIComponent(item.id)), {
                            method: 'PATCH',
                            credentials: 'same-origin',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                        });
                    }
                });
                list.append(link);
            });
        }

        function refresh() {
            fetch(feedUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (response) { if (!response.ok) throw new Error('Unable to load notifications'); return response.json(); })
                .then(function (data) { updateCount(data.unread_count || 0); render(data.notifications || []); })
                .catch(function () {
                    if (!list.children.length) list.append(element('div', 'notification-empty', 'Notifications are temporarily unavailable.'));
                });
        }

        readAll.addEventListener('click', function () {
            fetch(menu.dataset.readAllUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).then(function (response) {
                if (!response.ok) throw new Error('Unable to update notifications');
                refresh();
            });
        });

        refresh();
        window.setInterval(refresh, 30000);
        menu.addEventListener('toggle', function () { if (menu.open) refresh(); });
    })();
</script>

</body>
</html>
