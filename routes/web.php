<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Admin\ApiRequestLogController;
use App\Http\Controllers\Web\Admin\Auth\LoginController;
use App\Http\Controllers\Web\Admin\DashboardController;
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

        Route::get('/api-requests/{apiRequestLog}', [ApiRequestLogController::class, 'show'])
            ->middleware('web.permission:'.WebAdminPermissions::API_REQUEST_LOGS_VIEW)
            ->name('api-requests.show');
    });
});
