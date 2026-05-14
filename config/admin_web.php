<?php

declare(strict_types=1);

use App\Support\Web\WebAdminPermissions;

return [
    'enabled' => env('ADMIN_WEB_ENABLED', true),

    'template' => [
        'source' => 'https://github.com/eloicorreia/templateweb',
        'variant' => 'master',
        'asset_path' => 'vendor/templateweb/master/assets',
        'required_assets' => [
            'css/bootstrap.min.css',
            'css/app.min.css',
            'css/custom.min.css',
            'js/layout.js',
            'libs/bootstrap/js/bootstrap.bundle.min.js',
            'images/favicon.ico',
            'images/logo-dark.png',
            'images/logo-light.png',
            'images/logo-sm.png',
            'images/auth-one-bg.jpg',
        ],
    ],

    'permissions' => WebAdminPermissions::descriptions(),

    'logs' => [
        'default_period_days' => 1,
        'max_period_days' => 31,
        'per_page' => 15,
        'max_per_page' => 50,
        'preview_text_limit' => 1000,
        'detail_text_limit' => 4000,
        'max_payload_items' => 100,
    ],
];
