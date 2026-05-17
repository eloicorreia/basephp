<div id="admin-sidebar" class="app-menu navbar-menu" data-turbo-permanent>
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
                @forelse ($adminMenuGroups ?? [] as $group)
                    <li class="menu-title"><span>{{ $group['title'] }}</span></li>
                    @include('admin.partials.sidebar-menu-items', ['items' => $group['items'] ?? []])
                @empty
                    <li class="menu-title"><span>Menu</span></li>
                    <li class="nav-item">
                        <a class="nav-link menu-link disabled" href="#" aria-disabled="true">
                            <i class="ri-lock-line"></i>
                            <span>Nenhum menu disponível</span>
                        </a>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
