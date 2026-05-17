@php
    $templateAssets = asset((string) config('admin_web.template.asset_path'));
    $currentUser = auth('web')->user();
@endphp
<!doctype html>
<html
    lang="pt-BR"
    data-layout="vertical"
    data-topbar="light"
    data-sidebar="dark"
    data-sidebar-size="lg"
    data-layout-width="fluid"
    data-layout-position="fixed"
    data-layout-style="default"
    data-layout-direction="ltr"
    data-bs-theme="light"
    data-theme="default"
    data-theme-colors="default"
>
@include('admin.partials.head')
<body>
    <div id="layout-wrapper">
        @include('admin.partials.topbar')
        @include('admin.partials.sidebar')

        <div class="vertical-overlay"></div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
            @include('admin.partials.footer')
        </div>
    </div>

    @include('admin.partials.scripts')
</body>
</html>
