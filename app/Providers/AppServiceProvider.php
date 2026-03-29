<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Passport::enablePasswordGrant();

        RateLimiter::for('auth', function (Request $request): array {
            $identifier = (string) ($request->input('username') ?? $request->ip());

            return [
                Limit::perMinute(5)->by($identifier . '|' . $request->ip()),
            ];
        });

        RateLimiter::for('api', function (Request $request): array {
            $identifier = (string) ($request->user()?->id ?? $request->ip());

            return [
                Limit::perMinute(60)->by($identifier),
            ];
        });

        RateLimiter::for('strict', function (Request $request): array {
            $identifier = (string) ($request->user()?->id ?? $request->ip());

            return [
                Limit::perMinute(20)->by($identifier),
            ];
        });

        Passport::tokensCan([
            'admin.full' => 'Acesso administrativo total',
            'tenant.access' => 'Acesso ao tenant',
            'user.profile' => 'Acesso ao perfil autenticado',
        ]);
    }
}