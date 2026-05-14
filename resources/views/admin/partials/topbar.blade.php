<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex align-items-center">
                <div class="navbar-brand-box horizontal-logo">
                    <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
                        <span class="logo-sm"><img src="{{ $templateAssets }}/images/logo-sm.png" alt="" height="22"></span>
                        <span class="logo-lg"><img src="{{ $templateAssets }}/images/logo-dark.png" alt="{{ config('app.name', 'BasePHP') }}" height="18"></span>
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
                        <span class="logo-sm"><img src="{{ $templateAssets }}/images/logo-sm.png" alt="" height="22"></span>
                        <span class="logo-lg"><img src="{{ $templateAssets }}/images/logo-light.png" alt="{{ config('app.name', 'BasePHP') }}" height="18"></span>
                    </a>
                </div>

                <span class="fw-semibold ms-3">@yield('page-title', 'Administração')</span>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <div class="fw-medium">{{ $currentUser?->name }}</div>
                    <small class="text-muted">{{ $currentUser?->email }}</small>
                </div>

                <div class="dropdown">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ri-user-3-line fs-22"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">{{ $currentUser?->name }}</h6>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="ri-logout-box-r-line text-muted fs-16 align-middle me-1"></i>
                                <span class="align-middle">Sair</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
