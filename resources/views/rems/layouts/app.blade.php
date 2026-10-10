<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#152238">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('rems_ui.app_name', 'EstateHR') }}</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('rems/css/app.css') }}">
    @stack('styles')
</head>
<body>
    <div class="rems-shell" id="appShell">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="rems-sidebar" id="remsSidebar" aria-label="Main navigation">
            <a class="brand-wrap" href="{{ route('rems.dashboard') }}" aria-label="EstateHR home">
                <span class="brand-symbol"><i class="bi bi-buildings-fill"></i></span>
                <span class="brand-content"><strong>{{ config('rems_ui.app_name', 'EstateHR') }}</strong><small>REAL ESTATE HR SUITE</small></span>
            </a>
            <div class="company-pill"><span class="company-indicator"></span><div><span>Company workspace</span><small>Employee operations</small></div><i class="bi bi-check-circle-fill ms-auto text-success"></i></div>
            <div class="sidebar-scroll">
                <div class="nav-section-label">OVERVIEW</div>
                <a class="sidebar-link {{ request()->routeIs('rems.dashboard') ? 'active' : '' }}" href="{{ route('rems.dashboard') }}">
                    <i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span><i class="bi bi-chevron-right ms-auto nav-chevron"></i>
                </a>
                @foreach(config('rems_ui.groups', []) as $group => $resources)
                    @php
                        $visibleResources = collect($resources)->filter(fn ($slug) => auth()->user()->can(config("rems_ui.resources.$slug.view")));
                    @endphp
                    @if($visibleResources->isNotEmpty())
                        <div class="nav-section-label">{{ strtoupper($group) }}</div>
                        @foreach($visibleResources as $slug)
                            @php($item = config("rems_ui.resources.$slug"))
                            <a class="sidebar-link {{ request()->route('resource') === $slug ? 'active' : '' }}" href="{{ route('rems.resources.index',$slug) }}">
                                <i class="bi bi-{{ $item['icon'] }}"></i><span>{{ $item['title'] }}</span>
                                @if(request()->route('resource') === $slug) <i class="bi bi-chevron-right ms-auto nav-chevron"></i> @endif
                            </a>
                        @endforeach
                    @endif
                @endforeach
            </div>
            <div class="sidebar-footer">
                <div class="avatar avatar-sidebar">{{ strtoupper(mb_substr(auth()->user()->name ?? 'U',0,1)) }}</div>
                <div class="text-truncate"><strong class="d-block text-truncate">{{ auth()->user()->name }}</strong><small class="text-truncate d-block">{{ str_replace('_',' ', auth()->user()->getRoleNames()->first() ?? 'Staff') }}</small></div>
                <form action="{{ route('rems.logout') }}" method="post" class="ms-auto">@csrf<button type="submit" class="logout-icon" title="Sign out" aria-label="Sign out"><i class="bi bi-box-arrow-right"></i></button></form>
            </div>
        </aside>
        <div class="rems-main">
            <header class="rems-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="menu-toggle" id="menuToggle" aria-controls="remsSidebar" aria-expanded="false" aria-label="Toggle sidebar"><i class="bi bi-list"></i></button>
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <div class="d-none d-sm-block"><small class="topbar-eyebrow">Workspace</small><span class="topbar-location">@yield('title','Dashboard')</span></div>
                </div>
                <div class="d-flex align-items-center gap-2 gap-md-3">
                    <div class="topbar-date d-none d-lg-flex"><i class="bi bi-calendar3"></i><span>{{ now()->format('D, d M Y') }}</span></div>
                    <div class="topbar-profile dropdown">
                        <button class="btn dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar avatar-top">{{ strtoupper(mb_substr(auth()->user()->name ?? 'U',0,1)) }}</span>
                            <span class="d-none d-sm-block text-start"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                            <li><span class="dropdown-item-text text-muted small">Signed in as {{ auth()->user()->getRoleNames()->first() }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form action="{{ route('rems.logout') }}" method="post">@csrf<button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button></form></li>
                        </ul>
                    </div>
                </div>
            </header>
            <main class="rems-content" id="mainContent">
                @if(session('success'))<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                @if($errors->any())<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert"><strong>Some fields need attention.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
                @yield('content')
            </main>
            <footer class="rems-footer"><span>© {{ date('Y') }} {{ config('rems_ui.app_name', 'EstateHR') }} · Employee Operations</span><span class="d-none d-sm-inline">Built with Laravel & Bootstrap</span></footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="{{ asset('rems/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
