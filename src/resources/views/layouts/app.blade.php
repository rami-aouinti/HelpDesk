<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HelpDesk')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
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
            <a href="{{ route('dashboard') }}" class="nav-link active">
                Dashboard
            </a>
            <a href="#" class="nav-link">Tickets</a>
            <a href="#" class="nav-link">Customers</a>
            <a href="#" class="nav-link">Team</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="nav-label">PREFERENCES</div>
            <a href="#" class="nav-link">Settings</a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <span class="breadcrumb">Workspace /</span>
                <strong>@yield('page-title', 'Dashboard')</strong>
            </div>
            <div class="user-profile">
                <div class="avatar">RA</div>
                <span>Administrator</span>
            </div>
        </header>

        <section class="page-content">
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>
