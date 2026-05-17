<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Multitenancy\TenantContextInterface;
use App\Models\User;
use App\Services\Mail\Contracts\RuntimeMailSenderInterface;
use App\Services\Mail\Contracts\TenantMailConnectionTesterInterface;
use App\Services\Mail\SymfonyRuntimeMailSender;
use App\Services\Mail\SymfonyTenantMailConnectionTester;
use App\Services\Web\AdminMenuBuilderService;
use App\Support\Auth\OAuthScopes;
use App\Support\Tenant\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
        $this->app->alias(TenantContext::class, TenantContextInterface::class);
        $this->app->bind(RuntimeMailSenderInterface::class, SymfonyRuntimeMailSender::class);
        $this->app->bind(TenantMailConnectionTesterInterface::class, SymfonyTenantMailConnectionTester::class);
    }

    public function boot(): void
    {
        if ((bool) config('passport.enable_password_grant', false)) {
            Passport::enablePasswordGrant();
        }

        Passport::tokensCan(OAuthScopes::descriptions());

        RateLimiter::for('auth', function (Request $request): array {
            $identifier = (string) ($request->input('username') ?? $request->ip());

            return [
                Limit::perMinute(5)->by($identifier.'|'.$request->ip()),
            ];
        });

        RateLimiter::for('api', function (Request $request): array {
            $oauthClientId = $request->attributes->get('oauth_client_id');
            $userId = $request->user()?->getAuthIdentifier();
            $identifier = $oauthClientId !== null
                ? 'oauth-client:'.$oauthClientId
                : 'user-or-ip:'.($userId !== null ? (string) $userId : $request->ip());

            return [
                Limit::perMinute(60)->by($identifier),
            ];
        });

        RateLimiter::for('strict', function (Request $request): array {
            $userId = $request->user()?->getAuthIdentifier();
            $identifier = $userId !== null ? (string) $userId : $request->ip();

            return [
                Limit::perMinute(20)->by($identifier),
            ];
        });

        View::composer('admin.partials.sidebar', function ($view): void {
            $user = Auth::guard('web')->user();

            $view->with('adminMenuGroups', $user instanceof User
                ? app(AdminMenuBuilderService::class)->buildForUser($user)
                : []);
        });
    }
}
