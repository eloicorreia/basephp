<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\EmailDispatchController;
use App\Http\Controllers\Api\V1\Admin\FailedJobController;
use App\Http\Controllers\Api\V1\Admin\QueueController;
use App\Http\Controllers\Api\V1\Admin\TenantController;
use App\Http\Controllers\Api\V1\Admin\TenantUserController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\ChangePasswordController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Operação realizada com sucesso.',
            'data' => ['status' => 'ok'],
        ]);
    });

    Route::middleware([
        'client.credentials',
        'throttle:api',
        'scope:system.health',
    ])->get('/system/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'Operação realizada com sucesso.',
            'data' => ['area' => 'system'],
        ]);
    });

    Route::middleware([
        'auth:api',
        'throttle:api',
        'user.active',
    ])->group(function (): void {
        Route::post('/auth/change-password', ChangePasswordController::class)
            ->middleware(['throttle:strict', 'scope:user.password.change']);
    });

    Route::middleware([
        'auth:api',
        'throttle:api',
        'user.active',
        'scope:tenant.access',
        'tenant.resolve',
        'tenant.access',
        'password.changed',
    ])->group(function (): void {
        Route::get('/auth/me', MeController::class)
            ->middleware('scope:user.profile');

        Route::middleware(['role:admin'])->group(function (): void {
            Route::get('/admin/ping', function () {
                return response()->json([
                    'success' => true,
                    'message' => 'Operação realizada com sucesso.',
                    'data' => ['area' => 'admin'],
                ]);
            })->middleware('scope:admin.full');

            Route::get('/admin/tenants', [TenantController::class, 'index'])
                ->middleware('any_scope:admin.full,tenants.read');
            Route::post('/admin/tenants', [TenantController::class, 'store'])
                ->middleware('any_scope:admin.full,tenants.write');
            Route::get('/admin/tenants/{tenant}', [TenantController::class, 'show'])
                ->middleware('any_scope:admin.full,tenants.read');

            Route::get('/admin/users', [UserController::class, 'index'])
                ->middleware('any_scope:admin.full,users.read');
            Route::post('/admin/users', [UserController::class, 'store'])
                ->middleware('any_scope:admin.full,users.write');
            Route::get('/admin/users/{user}', [UserController::class, 'show'])
                ->middleware('any_scope:admin.full,users.read');

            Route::get('/admin/tenant-users', [TenantUserController::class, 'index'])
                ->middleware('any_scope:admin.full,tenant.users.read');
            Route::post('/admin/tenant-users', [TenantUserController::class, 'store'])
                ->middleware('any_scope:admin.full,tenant.users.write');

            Route::get('/admin/queues/catalog', [QueueController::class, 'catalog'])
                ->middleware('any_scope:admin.full,queues.read');
            Route::get('/admin/queues/summary', [QueueController::class, 'summary'])
                ->middleware('any_scope:admin.full,queues.read');
            Route::get('/admin/queues/jobs', [QueueController::class, 'index'])
                ->middleware('any_scope:admin.full,queues.read');
            Route::get('/admin/queues/jobs/{job}', [QueueController::class, 'show'])
                ->middleware('any_scope:admin.full,queues.read');

            Route::get('/admin/queues/failed-jobs', [FailedJobController::class, 'index'])
                ->middleware('any_scope:admin.full,queues.read');
            Route::get('/admin/queues/failed-jobs/{failedJob}', [FailedJobController::class, 'show'])
                ->middleware('any_scope:admin.full,queues.read');
            Route::post('/admin/queues/failed-jobs/{failedJob}/retry', [FailedJobController::class, 'retry'])
                ->middleware('any_scope:admin.full,queues.write');
            Route::delete('/admin/queues/failed-jobs/{failedJob}', [FailedJobController::class, 'destroy'])
                ->middleware('any_scope:admin.full,queues.write');

            Route::get('/admin/emails', [EmailDispatchController::class, 'index'])
                ->middleware('any_scope:admin.full,emails.read');
            Route::get('/admin/emails/{emailDispatch}', [EmailDispatchController::class, 'show'])
                ->middleware('any_scope:admin.full,emails.read');
            Route::post('/admin/emails/send', [EmailDispatchController::class, 'send'])
                ->middleware('any_scope:admin.full,emails.write');
            Route::post('/admin/emails/{emailDispatch}/retry', [EmailDispatchController::class, 'retry'])
                ->middleware('any_scope:admin.full,emails.write');
        });
    });
});
