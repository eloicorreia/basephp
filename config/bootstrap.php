<?php

declare(strict_types=1);

return [
    'admin_user' => [
        'name' => env('ADMIN_USER_NAME', 'Administrador'),
        'email' => env('ADMIN_USER_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_USER_PASSWORD'),
    ],

    'development_tenant' => [
        // Null means: enabled by default only in local/testing.
        // Production must explicitly set SEED_DEVELOPMENT_TENANT=true.
        'enabled' => env('SEED_DEVELOPMENT_TENANT'),
        'code' => env('DEVELOPMENT_TENANT_CODE', 'tenant-dev-001'),
        'name' => env('DEVELOPMENT_TENANT_NAME', 'Tenant Desenvolvimento 001'),
        'schema_name' => env('DEVELOPMENT_TENANT_SCHEMA_NAME', 'tenant_dev_001'),
    ],
];
