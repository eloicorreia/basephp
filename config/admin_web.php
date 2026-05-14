<?php

declare(strict_types=1);

use App\Support\Web\WebAdminPermissions;

return [
    'enabled' => env('ADMIN_WEB_ENABLED', true),

    'template' => [
        'source' => 'https://github.com/eloicorreia/templateweb',
        'variant' => 'master',
        'asset_path' => 'vendor/templateweb/master/assets',
    ],

    'permissions' => WebAdminPermissions::descriptions(),
];
