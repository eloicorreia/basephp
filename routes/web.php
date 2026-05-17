<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Admin\ApiRequestLogController;
use App\Http\Controllers\Web\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Admin\Auth\LoginController;
use App\Http\Controllers\Web\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\Security\SecurityController;
use App\Http\Controllers\Web\Admin\Security\SecurityPermissionController;
use App\Http\Controllers\Web\Admin\Security\SecurityRoleController;
use App\Http\Controllers\Web\Admin\Security\SecurityUserController;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Support\Facades\Route;

if (! (bool) config('admin_web.enabled', true)) {
    Route::get('/', function () {
        return view('welcome');
    });

    return;
}

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::middleware('guest:web')->group(function (): void {
    Route::get('/admin/login', [LoginController::class, 'show'])->name('login');
    Route::post('/admin/login', [LoginController::class, 'store'])->name('admin.login.store');

    Route::get('/admin/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/admin/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/admin/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/admin/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

Route::middleware([
    'auth:web',
    'web.permission:'.WebAdminPermissions::ACCESS,
])->prefix('admin')->name('admin.')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)
        ->middleware('web.permission:'.WebAdminPermissions::DASHBOARD_VIEW)
        ->name('dashboard');

    Route::prefix('logs')->name('logs.')->group(function (): void {
        Route::get('/api-requests', [ApiRequestLogController::class, 'index'])
            ->middleware('web.permission:'.WebAdminPermissions::API_REQUEST_LOGS_VIEW)
            ->name('api-requests.index');

        Route::get('/api-requests/{apiRequestLog}/payload', [ApiRequestLogController::class, 'payload'])
            ->middleware('web.permission:'.WebAdminPermissions::API_REQUEST_LOG_PAYLOADS_VIEW)
            ->name('api-requests.payload');

        Route::get('/api-requests/{apiRequestLog}', [ApiRequestLogController::class, 'show'])
            ->middleware('web.permission:'.WebAdminPermissions::API_REQUEST_LOGS_VIEW)
            ->name('api-requests.show');
    });

    Route::prefix('security')->name('security.')->group(function (): void {
        Route::get('/', SecurityController::class)
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_VIEW)
            ->name('index');

        Route::get('/users', [SecurityUserController::class, 'index'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_USERS_MANAGE)
            ->name('users.index');
        Route::get('/users/create', [SecurityUserController::class, 'create'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_USERS_MANAGE)
            ->name('users.create');
        Route::post('/users', [SecurityUserController::class, 'store'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_USERS_MANAGE)
            ->name('users.store');
        Route::get('/users/{user}/edit', [SecurityUserController::class, 'edit'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_USERS_MANAGE)
            ->name('users.edit');
        Route::put('/users/{user}', [SecurityUserController::class, 'update'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_USERS_MANAGE)
            ->name('users.update');

        Route::get('/roles', [SecurityRoleController::class, 'index'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_ROLES_MANAGE)
            ->name('roles.index');
        Route::get('/roles/create', [SecurityRoleController::class, 'create'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_ROLES_MANAGE)
            ->name('roles.create');
        Route::post('/roles', [SecurityRoleController::class, 'store'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_ROLES_MANAGE)
            ->name('roles.store');
        Route::get('/roles/{role}/edit', [SecurityRoleController::class, 'edit'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_ROLES_MANAGE)
            ->name('roles.edit');
        Route::put('/roles/{role}', [SecurityRoleController::class, 'update'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_ROLES_MANAGE)
            ->name('roles.update');

        Route::get('/permissions', [SecurityPermissionController::class, 'index'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_PERMISSIONS_MANAGE)
            ->name('permissions.index');
        Route::get('/permissions/create', [SecurityPermissionController::class, 'create'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_PERMISSIONS_MANAGE)
            ->name('permissions.create');
        Route::post('/permissions', [SecurityPermissionController::class, 'store'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_PERMISSIONS_MANAGE)
            ->name('permissions.store');
        Route::get('/permissions/{permission}/edit', [SecurityPermissionController::class, 'edit'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_PERMISSIONS_MANAGE)
            ->name('permissions.edit');
        Route::put('/permissions/{permission}', [SecurityPermissionController::class, 'update'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_PERMISSIONS_MANAGE)
            ->name('permissions.update');
        Route::patch('/permissions/{permission}/enable', [SecurityPermissionController::class, 'enable'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_PERMISSIONS_MANAGE)
            ->name('permissions.enable');
        Route::patch('/permissions/{permission}/disable', [SecurityPermissionController::class, 'disable'])
            ->middleware('web.permission:'.WebAdminPermissions::SECURITY_PERMISSIONS_MANAGE)
            ->name('permissions.disable');
    });
});
