<?php

declare(strict_types=1);

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
        'throttle:api',
        'auth:api',
        'user.active',
    ])->group(function (): void {
        Route::post('/auth/change-password', ChangePasswordController::class)
            ->middleware('throttle:strict');
    });

    Route::middleware([
        'throttle:api',
        'auth:api',
        'user.active',
        'tenant.resolve',
        'tenant.access',
        'password.changed',
    ])->group(function (): void {
        Route::get('/auth/me', MeController::class);

        Route::middleware(['role:admin'])->group(function (): void {
            Route::get('/admin/ping', function () {
                return response()->json([
                    'success' => true,
                    'message' => 'Operação realizada com sucesso.',
                    'data' => ['area' => 'admin'],
                ]);
            });

            Route::get('/admin/tenants', [TenantController::class, 'index']);
            Route::post('/admin/tenants', [TenantController::class, 'store']);
            Route::get('/admin/tenants/{tenant}', [TenantController::class, 'show']);

            Route::get('/admin/users', [UserController::class, 'index']);
            Route::post('/admin/users', [UserController::class, 'store']);
            Route::get('/admin/users/{user}', [UserController::class, 'show']);

            Route::get('/admin/tenant-users', [TenantUserController::class, 'index']);
            Route::post('/admin/tenant-users', [TenantUserController::class, 'store']);
        });
    });
});