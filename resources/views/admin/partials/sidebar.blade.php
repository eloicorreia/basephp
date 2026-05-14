<div class="app-menu navbar-menu">
    <div class="navbar-brand-box">
        <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
            <span class="logo-sm"><img src="{{ $templateAssets }}/images/logo-sm.png" alt="" height="22"></span>
            <span class="logo-lg"><img src="{{ $templateAssets }}/images/logo-dark.png" alt="{{ config('app.name', 'BasePHP') }}" height="18"></span>
        </a>
        <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
            <span class="logo-sm"><img src="{{ $templateAssets }}/images/logo-sm.png" alt="" height="22"></span>
            <span class="logo-lg"><img src="{{ $templateAssets }}/images/logo-light.png" alt="{{ config('app.name', 'BasePHP') }}" height="20"></span>
        </a>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>

            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>Menu</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="#sidebarDashboards" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('admin.dashboard') ? 'true' : 'false' }}" aria-controls="sidebarDashboards">
                        <i class="ri-dashboard-2-line"></i>
                        <span>Dashboards</span>
                    </a>
                    <div class="collapse menu-dropdown {{ request()->routeIs('admin.dashboard') ? 'show' : '' }}" id="sidebarDashboards">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Operacional</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="menu-title"><span>Operação</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}" href="{{ route('admin.logs.api-requests.index') }}">
                        <i class="ri-file-list-3-line"></i>
                        <span>Logs da API</span>
                    </a>
                </li>

                <li class="menu-title"><span>Base</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link disabled" href="#" aria-disabled="true">
                        <i class="ri-settings-3-line"></i>
                        <span>Configurações</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
