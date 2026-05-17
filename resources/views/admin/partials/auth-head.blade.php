<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Login administrativo') | {{ config('app.name', 'BasePHP') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ $templateAssets }}/images/favicon.ico">
    <link rel="preload" href="{{ $templateAssets }}/css/bootstrap.min.css" as="style">
    <link rel="preload" href="{{ $templateAssets }}/css/app.min.css" as="style">
    <link rel="preload" href="{{ $templateAssets }}/css/admin-contract.css" as="style">
    <script src="{{ $templateAssets }}/js/layout.js"></script>
    <link href="{{ $templateAssets }}/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="{{ $templateAssets }}/css/icons.min.css" rel="stylesheet" type="text/css">
    <link href="{{ $templateAssets }}/css/app.min.css" rel="stylesheet" type="text/css">
    <link href="{{ $templateAssets }}/css/custom.min.css" rel="stylesheet" type="text/css">
    <link href="{{ $templateAssets }}/css/admin-contract.css" rel="stylesheet" type="text/css">
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            document.documentElement.classList.add('admin-layout-ready');
        });
    </script>
    @stack('styles')
</head>
