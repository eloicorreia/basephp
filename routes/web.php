<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Admin\ApiRequestLogController;
use App\Http\Controllers\Web\Admin\Auth\ChangePasswordController;
use App\Http\Controllers\Web\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Admin\Auth\LoginController;
use App\Http\Controllers\Web\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\Security\SecurityController;
use App\Http\Controllers\Web\Admin\Security\SecurityPermissionController;
use App\Http\Controllers\Web\Admin\Security\SecurityRoleController;
use App\Http\Controllers\Web\Admin\Security\SecurityUserController;
use App\Http\Controllers\Web\Admin\SystemSettings\ApiSettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\AuditSettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\GeneralSettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\IntegrationSettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\MailSettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\NotificationSettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\PasswordPolicyController;
use App\Http\Controllers\Web\Admin\SystemSettings\QueueSettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\SecuritySettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\SystemSettingsController;
use App\Http\Controllers\Web\Admin\SystemSettings\WebhookSettingsController;
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

    Route::get('/password/change', [ChangePasswordController::class, 'show'])->name('password.change');
    Route::put('/password/change', [ChangePasswordController::class, 'update'])->name('password.update');

    Route::middleware('web.password.changed')->group(function (): void {
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

        Route::prefix('system-settings')->name('system-settings.')->group(function (): void {
            Route::get('/', SystemSettingsController::class)
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_VIEW)
                ->name('index');

            Route::get('/general', [GeneralSettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_GENERAL_MANAGE)
                ->name('general.edit');
            Route::put('/general', [GeneralSettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_GENERAL_MANAGE)
                ->name('general.update');

            Route::get('/security', [SecuritySettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_SECURITY_MANAGE)
                ->name('security.edit');
            Route::put('/security', [SecuritySettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_SECURITY_MANAGE)
                ->name('security.update');

            Route::get('/password-policy', [PasswordPolicyController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_PASSWORD_POLICY_MANAGE)
                ->name('password-policy.edit');
            Route::put('/password-policy', [PasswordPolicyController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_PASSWORD_POLICY_MANAGE)
                ->name('password-policy.update');

            Route::get('/mail', [MailSettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_MAIL_MANAGE)
                ->name('mail.edit');
            Route::put('/mail', [MailSettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_MAIL_MANAGE)
                ->name('mail.update');
            Route::post('/mail/test', [MailSettingsController::class, 'test'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_MAIL_MANAGE)
                ->name('mail.test');

            Route::get('/api', [ApiSettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_API_MANAGE)
                ->name('api.edit');
            Route::put('/api', [ApiSettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_API_MANAGE)
                ->name('api.update');

            Route::get('/queues', [QueueSettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_QUEUE_MANAGE)
                ->name('queues.edit');
            Route::put('/queues', [QueueSettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_QUEUE_MANAGE)
                ->name('queues.update');

            Route::get('/audit', [AuditSettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_AUDIT_MANAGE)
                ->name('audit.edit');
            Route::put('/audit', [AuditSettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_AUDIT_MANAGE)
                ->name('audit.update');

            Route::get('/integrations', [IntegrationSettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_INTEGRATION_MANAGE)
                ->name('integrations.edit');
            Route::put('/integrations', [IntegrationSettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_INTEGRATION_MANAGE)
                ->name('integrations.update');

            Route::get('/webhooks', [WebhookSettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_WEBHOOK_MANAGE)
                ->name('webhooks.edit');
            Route::put('/webhooks', [WebhookSettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_WEBHOOK_MANAGE)
                ->name('webhooks.update');

            Route::get('/notifications', [NotificationSettingsController::class, 'edit'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_NOTIFICATION_MANAGE)
                ->name('notifications.edit');
            Route::put('/notifications', [NotificationSettingsController::class, 'update'])
                ->middleware('web.permission:'.WebAdminPermissions::SYSTEM_SETTINGS_NOTIFICATION_MANAGE)
                ->name('notifications.update');
        });
    });
});
