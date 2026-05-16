<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\EmailDispatchController;
use App\Http\Controllers\Api\V1\Admin\FailedJobController;
use App\Http\Controllers\Api\V1\Admin\PermissionController;
use App\Http\Controllers\Api\V1\Admin\QueueController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\Admin\TenantController;
use App\Http\Controllers\Api\V1\Admin\TenantUserController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Support\Auth\OAuthScopes;
use App\Support\Auth\PermissionRegistry;
use App\Support\Http\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function () {
        return ApiResponse::success(['status' => 'ok']);
    });

    Route::middleware([
        'client.credentials',
        'throttle:api',
        OAuthScopes::scope(OAuthScopes::SYSTEM_HEALTH),
    ])->get('/system/ping', function () {
        return ApiResponse::success(['area' => 'system']);
    });

    Route::middleware([
        'auth:api',
        'throttle:api',
        'user.active',
    ])->group(function (): void {
        Route::post('/auth/change-password', ChangePasswordController::class)
            ->middleware(['throttle:strict', OAuthScopes::scope(OAuthScopes::USER_PASSWORD_CHANGE)]);
    });

    Route::middleware([
        'auth:api',
        'throttle:api',
        'user.active',
        OAuthScopes::scope(OAuthScopes::TENANT_ACCESS),
        'tenant.resolve',
        'tenant.access',
        'password.changed',
    ])->group(function (): void {
        Route::get('/auth/me', MeController::class)
            ->middleware(OAuthScopes::scope(OAuthScopes::USER_PROFILE));

        Route::middleware(['role:admin'])->group(function (): void {
            Route::get('/admin/ping', function () {
                return ApiResponse::success(['area' => 'admin']);
            })->middleware([
                OAuthScopes::scope(OAuthScopes::ADMIN_FULL),
                'permission:'.PermissionRegistry::ADMIN_FULL,
            ]);

            Route::get('/admin/tenants', [TenantController::class, 'index'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::TENANTS_READ), 'permission:'.PermissionRegistry::TENANTS_READ]);
            Route::post('/admin/tenants', [TenantController::class, 'store'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::TENANTS_WRITE), 'permission:'.PermissionRegistry::TENANTS_WRITE]);
            Route::get('/admin/tenants/{tenant}', [TenantController::class, 'show'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::TENANTS_READ), 'permission:'.PermissionRegistry::TENANTS_READ]);

            Route::get('/admin/users', [UserController::class, 'index'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::USERS_READ), 'permission:'.PermissionRegistry::USERS_READ]);
            Route::post('/admin/users', [UserController::class, 'store'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::USERS_WRITE), 'permission:'.PermissionRegistry::USERS_WRITE]);
            Route::get('/admin/users/{user}', [UserController::class, 'show'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::USERS_READ), 'permission:'.PermissionRegistry::USERS_READ]);
            Route::patch('/admin/users/{user}/role', [UserController::class, 'assignRole'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::USERS_WRITE), 'permission:'.PermissionRegistry::USERS_WRITE]);

            Route::get('/admin/roles', [RoleController::class, 'index'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::ROLES_READ), 'permission:'.PermissionRegistry::ROLES_READ]);
            Route::get('/admin/roles/{role}', [RoleController::class, 'show'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::ROLES_READ), 'permission:'.PermissionRegistry::ROLES_READ]);
            Route::get('/admin/roles/{role}/permissions', [RoleController::class, 'permissions'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::ROLES_READ), 'permission:'.PermissionRegistry::ROLES_READ]);
            Route::put('/admin/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::ROLES_WRITE), 'permission:'.PermissionRegistry::ROLES_WRITE]);

            Route::get('/admin/permissions', [PermissionController::class, 'index'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::PERMISSIONS_READ), 'permission:'.PermissionRegistry::PERMISSIONS_READ]);
            Route::post('/admin/permissions', [PermissionController::class, 'store'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::PERMISSIONS_WRITE), 'permission:'.PermissionRegistry::PERMISSIONS_WRITE]);
            Route::put('/admin/permissions/{permission}', [PermissionController::class, 'update'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::PERMISSIONS_WRITE), 'permission:'.PermissionRegistry::PERMISSIONS_WRITE]);
            Route::patch('/admin/permissions/{permission}/enable', [PermissionController::class, 'enable'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::PERMISSIONS_WRITE), 'permission:'.PermissionRegistry::PERMISSIONS_WRITE]);
            Route::patch('/admin/permissions/{permission}/disable', [PermissionController::class, 'disable'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::PERMISSIONS_WRITE), 'permission:'.PermissionRegistry::PERMISSIONS_WRITE]);

            Route::get('/admin/tenant-users', [TenantUserController::class, 'index'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::TENANT_USERS_READ), 'permission:'.PermissionRegistry::TENANT_USERS_READ]);
            Route::post('/admin/tenant-users', [TenantUserController::class, 'store'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::TENANT_USERS_WRITE), 'permission:'.PermissionRegistry::TENANT_USERS_WRITE]);

            Route::get('/admin/queues/catalog', [QueueController::class, 'catalog'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::QUEUES_READ), 'permission:'.PermissionRegistry::QUEUES_READ]);
            Route::get('/admin/queues/summary', [QueueController::class, 'summary'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::QUEUES_READ), 'permission:'.PermissionRegistry::QUEUES_READ]);
            Route::get('/admin/queues/jobs', [QueueController::class, 'index'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::QUEUES_READ), 'permission:'.PermissionRegistry::QUEUES_READ]);
            Route::get('/admin/queues/jobs/{job}', [QueueController::class, 'show'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::QUEUES_READ), 'permission:'.PermissionRegistry::QUEUES_READ]);

            Route::get('/admin/queues/failed-jobs', [FailedJobController::class, 'index'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::QUEUES_READ), 'permission:'.PermissionRegistry::QUEUES_READ]);
            Route::get('/admin/queues/failed-jobs/{failedJob}', [FailedJobController::class, 'show'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::QUEUES_READ), 'permission:'.PermissionRegistry::QUEUES_READ]);
            Route::post('/admin/queues/failed-jobs/{failedJob}/retry', [FailedJobController::class, 'retry'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::QUEUES_WRITE), 'permission:'.PermissionRegistry::QUEUES_WRITE]);
            Route::delete('/admin/queues/failed-jobs/{failedJob}', [FailedJobController::class, 'destroy'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::QUEUES_WRITE), 'permission:'.PermissionRegistry::QUEUES_WRITE]);

            Route::get('/admin/emails', [EmailDispatchController::class, 'index'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::EMAILS_READ), 'permission:'.PermissionRegistry::EMAILS_READ]);
            Route::get('/admin/emails/{emailDispatch}', [EmailDispatchController::class, 'show'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::EMAILS_READ), 'permission:'.PermissionRegistry::EMAILS_READ]);
            Route::post('/admin/emails/send', [EmailDispatchController::class, 'send'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::EMAILS_WRITE), 'permission:'.PermissionRegistry::EMAILS_WRITE]);
            Route::post('/admin/emails/{emailDispatch}/retry', [EmailDispatchController::class, 'retry'])
                ->middleware([OAuthScopes::any(OAuthScopes::ADMIN_FULL, OAuthScopes::EMAILS_WRITE), 'permission:'.PermissionRegistry::EMAILS_WRITE]);
        });
    });
});
