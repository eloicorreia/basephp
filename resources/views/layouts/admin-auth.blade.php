@php
    $templateAssets = asset((string) config('admin_web.template.asset_path'));
@endphp
<!doctype html>
<html lang="pt-BR" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Login administrativo') | {{ config('app.name', 'BasePHP') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ $templateAssets }}/images/favicon.ico">
    <script src="{{ $templateAssets }}/js/layout.js"></script>
    <link href="{{ $templateAssets }}/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="{{ $templateAssets }}/css/app.min.css" rel="stylesheet" type="text/css">
    <link href="{{ $templateAssets }}/css/custom.min.css" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="auth-page-wrapper pt-5">
        <div class="auth-one-bg-position auth-one-bg">
            <div class="bg-overlay"></div>
            <div class="shape">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120">
                    <path d="M0,36 C144,53.6 432,123.2 720,124 C1008,124.8 1296,56.8 1440,40 L1440,140 L0,140z"></path>
                </svg>
            </div>
        </div>

        <div class="auth-page-content">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="text-center mt-sm-5 mb-4 text-white-50">
                            <a href="{{ route('login') }}" class="d-inline-block auth-logo">
                                <img src="{{ $templateAssets }}/images/logo-light.png" alt="{{ config('app.name', 'BasePHP') }}" height="22">
                            </a>
                            <p class="mt-3 fs-15 fw-medium">Módulo web administrativo</p>
                        </div>
                    </div>
                </div>

                @yield('content')
            </div>
        </div>

        <footer class="footer">
            <div class="container">
                <div class="text-center">
                    <p class="mb-0 text-muted">&copy; {{ now()->year }} {{ config('app.name', 'BasePHP') }}</p>
                </div>
            </div>
        </footer>
    </div>

    <script src="{{ $templateAssets }}/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
