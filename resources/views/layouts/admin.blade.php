@php
    $templateAssets = asset((string) config('admin_web.template.asset_path'));
    $currentUser = auth('web')->user();
@endphp
<!doctype html>
<html lang="pt-BR" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-theme="default">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Administração') | {{ config('app.name', 'BasePHP') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ $templateAssets }}/images/favicon.ico">
    <script src="{{ $templateAssets }}/js/layout.js"></script>
    <link href="{{ $templateAssets }}/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="{{ $templateAssets }}/css/app.min.css" rel="stylesheet" type="text/css">
    <link href="{{ $templateAssets }}/css/custom.min.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="layout-width">
                <div class="navbar-header">
                    <div class="d-flex align-items-center">
                        <div class="navbar-brand-box horizontal-logo">
                            <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
                                <span class="logo-sm"><img src="{{ $templateAssets }}/images/logo-sm.png" alt="" height="22"></span>
                                <span class="logo-lg"><img src="{{ $templateAssets }}/images/logo-dark.png" alt="{{ config('app.name', 'BasePHP') }}" height="18"></span>
                            </a>
                        </div>
                        <span class="fw-semibold ms-3">@yield('page-title', 'Administração')</span>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end d-none d-sm-block">
                            <div class="fw-medium">{{ $currentUser?->name }}</div>
                            <small class="text-muted">{{ $currentUser?->email }}</small>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Sair</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <div class="app-menu navbar-menu">
            <div class="navbar-brand-box">
                <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
                    <span class="logo-sm"><img src="{{ $templateAssets }}/images/logo-sm.png" alt="" height="22"></span>
                    <span class="logo-lg"><img src="{{ $templateAssets }}/images/logo-light.png" alt="{{ config('app.name', 'BasePHP') }}" height="20"></span>
                </a>
            </div>
            <div class="navbar-menu overflow-auto">
                <ul class="navbar-nav">
                    <li class="menu-title"><span>Administração</span></li>
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link menu-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}" href="{{ route('admin.logs.api-requests.index') }}">
                            <span>Logs da API</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="vertical-overlay"></div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="{{ $templateAssets }}/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
