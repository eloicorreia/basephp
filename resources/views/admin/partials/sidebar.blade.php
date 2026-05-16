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

                @foreach ($adminMenuGroups ?? [] as $group)
                    <li class="menu-title"><span>{{ $group['title'] }}</span></li>
                    @include('admin.partials.sidebar-menu-items', ['items' => $group['items'] ?? []])
                @endforeach
            </ul>
        </div>
    </div>
</div>
