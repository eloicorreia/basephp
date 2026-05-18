<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use App\Support\Auth\OAuthScopes;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BaseContractArchitectureTest extends TestCase
{
    public function test_public_and_system_routes_keep_expected_security_contract(): void
    {
        $healthMiddleware = $this->middlewareFor('GET', 'api/v1/health');
        $this->assertRouteDoesNotHaveMiddleware($healthMiddleware, 'auth:api', 'api/v1/health');

        $systemPingMiddleware = $this->middlewareFor('GET', 'api/v1/system/ping');
        $this->assertRouteHasMiddleware($systemPingMiddleware, 'client.credentials', 'api/v1/system/ping');
        $this->assertRouteHasMiddleware(
            $systemPingMiddleware,
            OAuthScopes::scope(OAuthScopes::SYSTEM_HEALTH),
            'api/v1/system/ping'
        );
        $this->assertRouteDoesNotHaveMiddleware($systemPingMiddleware, 'auth:api', 'api/v1/system/ping');
    }

    public function test_user_routes_keep_expected_oauth_and_tenancy_contracts(): void
    {
        $changePasswordMiddleware = $this->middlewareFor('POST', 'api/v1/auth/change-password');
        $this->assertRouteHasMiddleware($changePasswordMiddleware, 'auth:api', 'api/v1/auth/change-password');
        $this->assertRouteHasMiddleware($changePasswordMiddleware, 'throttle:api', 'api/v1/auth/change-password');
        $this->assertRouteHasMiddleware($changePasswordMiddleware, 'throttle:strict', 'api/v1/auth/change-password');
        $this->assertRouteHasMiddleware($changePasswordMiddleware, 'user.active', 'api/v1/auth/change-password');
        $this->assertRouteHasMiddleware(
            $changePasswordMiddleware,
            OAuthScopes::scope(OAuthScopes::USER_PASSWORD_CHANGE),
            'api/v1/auth/change-password'
        );
        $this->assertRouteDoesNotHaveMiddleware($changePasswordMiddleware, 'tenant.resolve', 'api/v1/auth/change-password');

        $meMiddleware = $this->middlewareFor('GET', 'api/v1/auth/me');

        foreach ($this->tenantAwareUserMiddleware() as $middleware) {
            $this->assertRouteHasMiddleware($meMiddleware, $middleware, 'api/v1/auth/me');
        }

        $this->assertRouteHasMiddleware(
            $meMiddleware,
            OAuthScopes::scope(OAuthScopes::USER_PROFILE),
            'api/v1/auth/me'
        );
    }

    public function test_admin_routes_keep_expected_oauth_role_and_tenancy_contracts(): void
    {
        $adminRoutes = array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            static fn (LaravelRoute $route): bool => str_starts_with($route->uri(), 'api/v1/admin/')
        ));

        $this->assertNotEmpty($adminRoutes);

        foreach ($adminRoutes as $route) {
            $middleware = $route->gatherMiddleware();

            foreach ([...$this->tenantAwareUserMiddleware(), 'role:admin'] as $expectedMiddleware) {
                $this->assertRouteHasMiddleware($middleware, $expectedMiddleware, $route->uri());
            }

            $this->assertTrue(
                $this->routeMiddlewareContainsScope($middleware, OAuthScopes::ADMIN_FULL),
                sprintf('Route [%s] must allow the [%s] scope.', $route->uri(), OAuthScopes::ADMIN_FULL)
            );
        }
    }

    public function test_web_admin_routes_keep_expected_session_permission_contracts(): void
    {
        foreach ([
            ['GET', 'admin/login'],
            ['POST', 'admin/login'],
            ['GET', 'admin/forgot-password'],
            ['POST', 'admin/forgot-password'],
            ['GET', 'admin/reset-password/{token}'],
            ['POST', 'admin/reset-password'],
        ] as [$method, $uri]) {
            $middleware = $this->middlewareFor($method, $uri);

            $this->assertRouteHasMiddleware($middleware, 'guest:web', $uri);
            $this->assertRouteDoesNotHaveMiddleware($middleware, 'auth:web', $uri);
            $this->assertRouteDoesNotHaveMiddleware($middleware, 'auth:api', $uri);
        }

        foreach ([
            ['POST', 'admin/forgot-password'],
            ['POST', 'admin/reset-password'],
        ] as [$method, $uri]) {
            $this->assertRouteHasMiddleware($this->middlewareFor($method, $uri), 'throttle:6,1', $uri);
        }

        $dashboardMiddleware = $this->middlewareFor('GET', 'admin');

        $this->assertRouteHasMiddleware($dashboardMiddleware, 'auth:web', 'admin');
        $this->assertRouteHasMiddleware(
            $dashboardMiddleware,
            'web.permission:'.WebAdminPermissions::ACCESS,
            'admin'
        );
        $this->assertRouteHasMiddleware(
            $dashboardMiddleware,
            'web.permission:'.WebAdminPermissions::DASHBOARD_VIEW,
            'admin'
        );
        $this->assertRouteDoesNotHaveMiddleware($dashboardMiddleware, 'auth:api', 'admin');

        foreach (['admin/logs/api-requests', 'admin/logs/api-requests/{apiRequestLog}'] as $uri) {
            $middleware = $this->middlewareFor('GET', $uri);

            $this->assertRouteHasMiddleware($middleware, 'auth:web', $uri);
            $this->assertRouteHasMiddleware($middleware, 'web.permission:'.WebAdminPermissions::ACCESS, $uri);
            $this->assertRouteHasMiddleware(
                $middleware,
                'web.permission:'.WebAdminPermissions::API_REQUEST_LOGS_VIEW,
                $uri
            );
            $this->assertRouteDoesNotHaveMiddleware($middleware, 'auth:api', $uri);
        }

        $payloadMiddleware = $this->middlewareFor('GET', 'admin/logs/api-requests/{apiRequestLog}/payload');
        $this->assertRouteHasMiddleware($payloadMiddleware, 'auth:web', 'admin/logs/api-requests/{apiRequestLog}/payload');
        $this->assertRouteHasMiddleware(
            $payloadMiddleware,
            'web.permission:'.WebAdminPermissions::API_REQUEST_LOG_PAYLOADS_VIEW,
            'admin/logs/api-requests/{apiRequestLog}/payload'
        );
        $this->assertRouteDoesNotHaveMiddleware($payloadMiddleware, 'auth:api', 'admin/logs/api-requests/{apiRequestLog}/payload');
    }

    public function test_web_admin_template_contract_is_configured(): void
    {
        $this->assertSame('https://github.com/eloicorreia/templateweb', config('admin_web.template.source'));
        $this->assertSame('master', config('admin_web.template.variant'));
        $this->assertSame('vendor/templateweb/master/assets', config('admin_web.template.asset_path'));

        $this->assertDirectoryExists(public_path('vendor/templateweb/master/assets'));
        $this->assertFileExists(public_path('vendor/templateweb/master/assets/css/bootstrap.min.css'));
        $this->assertFileExists(public_path('vendor/templateweb/master/assets/css/icons.min.css'));
        $this->assertFileExists(public_path('vendor/templateweb/master/assets/css/icons.min.css.map'));
        $this->assertFileExists(public_path('vendor/templateweb/master/assets/css/app.min.css'));
        $this->assertFileExists(public_path('vendor/templateweb/master/assets/css/admin-contract.css'));
        $this->assertFileExists(public_path('vendor/templateweb/master/assets/js/pages/password-addon.init.js'));
        $this->assertFileExists(public_path('vendor/templateweb/master/assets/fonts/hkgrotesk-regular.woff2'));
        $this->assertFileExists(public_path('vendor/templateweb/master/assets/fonts/remixicon.woff2'));
        $this->assertContains('css/bootstrap.min.css', config('admin_web.template.required_assets'));
        $this->assertContains('css/icons.min.css', config('admin_web.template.required_assets'));
        $this->assertContains('css/admin-contract.css', config('admin_web.template.required_assets'));
        $this->assertContains('js/layout.js', config('admin_web.template.required_assets'));
        $this->assertContains('js/pages/password-addon.init.js', config('admin_web.template.required_assets'));
    }

    public function test_all_route_oauth_scopes_are_registered_in_the_central_contract(): void
    {
        $knownScopes = OAuthScopes::all();
        $usedScopes = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            foreach ($this->extractScopes($route->gatherMiddleware()) as $scope) {
                $usedScopes[] = $scope;
            }
        }

        $this->assertNotEmpty($usedScopes);

        foreach (array_unique($usedScopes) as $scope) {
            $this->assertContains($scope, $knownScopes, sprintf('Scope [%s] is not registered in OAuthScopes.', $scope));
        }
    }

    public function test_openapi_oauth_scopes_are_synchronized_with_the_application_contract(): void
    {
        $flows = config('l5-swagger.defaults.securityDefinitions.securitySchemes.passport.flows');

        $this->assertSame(
            OAuthScopes::authorizationCodeDescriptions(),
            $flows['authorizationCode']['scopes']
        );

        $this->assertSame(
            OAuthScopes::clientCredentialsDescriptions(),
            $flows['clientCredentials']['scopes']
        );
    }

    public function test_api_controllers_use_the_standard_response_contract(): void
    {
        $controllers = File::allFiles(app_path('Http/Controllers/Api'));

        $this->assertNotEmpty($controllers);

        foreach ($controllers as $controller) {
            $contents = File::get($controller->getPathname());

            $this->assertStringNotContainsString(
                'response()->json',
                $contents,
                sprintf('Controller [%s] must use ApiResponse instead of manual JSON responses.', $controller->getRelativePathname())
            );

            $this->assertStringContainsString(
                'ApiResponse::',
                $contents,
                sprintf('Controller [%s] must return responses through ApiResponse.', $controller->getRelativePathname())
            );
        }
    }

    public function test_exception_handler_uses_the_standard_error_response_contract(): void
    {
        $contents = File::get(base_path('bootstrap/app.php'));

        $this->assertStringContainsString('ApiResponse::error', $contents);
        $this->assertStringNotContainsString('return response()->json([', $contents);
    }

    #[DataProvider('forbiddenTenantControllerPatterns')]
    public function test_http_controllers_do_not_manipulate_tenant_context_or_schema(string $pattern): void
    {
        foreach (File::allFiles(app_path('Http/Controllers')) as $controller) {
            $contents = File::get($controller->getPathname());

            $this->assertStringNotContainsString(
                $pattern,
                $contents,
                sprintf('Controller [%s] must not manipulate tenancy directly.', $controller->getRelativePathname())
            );
        }
    }

    public function test_search_path_changes_are_restricted_to_tenant_infrastructure_services(): void
    {
        $allowedFiles = [
            'Services/Tenant/TenantSearchPathService.php',
            'Services/Tenant/TenantSchemaService.php',
        ];

        foreach (File::allFiles(app_path()) as $file) {
            $contents = File::get($file->getPathname());

            if (! str_contains($contents, 'SET search_path')) {
                continue;
            }

            $this->assertContains(
                $file->getRelativePathname(),
                $allowedFiles,
                sprintf('Search path manipulation must stay in tenant infrastructure services. Found in [%s].', $file->getRelativePathname())
            );
        }
    }

    public function test_tenant_security_policy_does_not_use_global_user_lock_fields(): void
    {
        $contents = File::get(app_path('Services/TenantSettings/TenantSecurityPolicyService.php'));

        $this->assertStringContainsString('TenantUserSecurityState', $contents);
        $this->assertStringContainsString('tenant_user_security_states', $contents);

        foreach (['failed_login_attempts', 'locked_until', 'locked_by_admin'] as $legacyField) {
            $this->assertStringNotContainsString(
                '$user->'.$legacyField,
                $contents,
                sprintf('Tenant security policy must not read or write users.%s.', $legacyField)
            );
        }
    }

    public function test_observability_defaults_keep_safe_logging_contract(): void
    {
        $allowedHeaders = config('observability.api_request_logging.allowed_headers');

        $this->assertTrue((bool) config('observability.api_request_logging.enabled'));
        $this->assertContains('authorization', $allowedHeaders);
        $this->assertContains('x-api-key', $allowedHeaders);
        $this->assertGreaterThan(0, config('observability.api_request_logging.max_text_length'));
        $this->assertSame('created_at', config('observability.retention.public_tables.api_request_logs.column'));
        $this->assertGreaterThan(0, config('observability.retention.public_tables.api_request_logs.days'));
    }

    public function test_readme_documents_the_official_base_contracts(): void
    {
        $readme = File::get(base_path('README.md'));

        foreach ([
            'Contratos rígidos da base oficial',
            'Contrato REST',
            'Contrato Web Administrativo',
            'Contrato OAuth2',
            'Contrato de tenancy',
            'Contrato de logging e observabilidade',
            'Contrato de exception handler',
            'Contrato de testes',
        ] as $requiredSection) {
            $this->assertStringContainsString($requiredSection, $readme);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function forbiddenTenantControllerPatterns(): array
    {
        return [
            'tenant context' => ['TenantContext'],
            'tenant search path service' => ['TenantSearchPathService'],
            'tenant schema service' => ['TenantSchemaService'],
            'manual search path' => ['SET search_path'],
            'manual database statement' => ['DB::statement'],
            'tenant header parsing' => ['X-Tenant-Id'],
        ];
    }

    /**
     * @return list<string>
     */
    private function middlewareFor(string $method, string $uri): array
    {
        return $this->routeFor($method, $uri)->gatherMiddleware();
    }

    private function routeFor(string $method, string $uri): LaravelRoute
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $uri && in_array($method, $route->methods(), true)) {
                return $route;
            }
        }

        $this->fail(sprintf('Route [%s %s] was not found.', $method, $uri));
    }

    /**
     * @return list<string>
     */
    private function tenantAwareUserMiddleware(): array
    {
        return [
            'auth:api',
            'throttle:api',
            'user.active',
            OAuthScopes::scope(OAuthScopes::TENANT_ACCESS),
            'tenant.resolve',
            'tenant.access',
            'password.changed',
        ];
    }

    /**
     * @param  array<int, mixed>  $middleware
     */
    private function assertRouteHasMiddleware(array $middleware, string $expected, string $routeUri): void
    {
        $this->assertContains($expected, $middleware, sprintf('Route [%s] is missing middleware [%s].', $routeUri, $expected));
    }

    /**
     * @param  array<int, mixed>  $middleware
     */
    private function assertRouteDoesNotHaveMiddleware(array $middleware, string $unexpected, string $routeUri): void
    {
        $this->assertNotContains($unexpected, $middleware, sprintf('Route [%s] must not use middleware [%s].', $routeUri, $unexpected));
    }

    /**
     * @param  array<int, mixed>  $middleware
     */
    private function routeMiddlewareContainsScope(array $middleware, string $expectedScope): bool
    {
        return in_array($expectedScope, $this->extractScopes($middleware), true);
    }

    /**
     * @param  array<int, mixed>  $middleware
     * @return list<string>
     */
    private function extractScopes(array $middleware): array
    {
        $scopes = [];

        foreach ($middleware as $item) {
            if (! is_string($item)) {
                continue;
            }

            if (str_starts_with($item, 'scope:')) {
                $scopes[] = substr($item, strlen('scope:'));
            }

            if (str_starts_with($item, 'any_scope:')) {
                $scopes = [
                    ...$scopes,
                    ...array_filter(explode(',', substr($item, strlen('any_scope:')))),
                ];
            }
        }

        return array_values(array_unique($scopes));
    }
}
