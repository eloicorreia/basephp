<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class EnsureTenantAccessMiddlewareTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['api', 'auth:api', 'tenant.resolve', 'tenant.access'])
            ->prefix('api/v1/test/middleware')
            ->get('/tenant-access', fn () => response()->json(['success' => true]));
    }

    public function test_it_blocks_user_without_tenant_membership(): void
    {
        $tenant = $this->createTenant(code: $this->tenantCode('no-membership'));
        $user = $this->createUser();

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/test/middleware/tenant-access', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Acesso negado.',
            'errors' => [],
        ]);
    }

    public function test_it_blocks_user_with_inactive_tenant_membership(): void
    {
        $tenant = $this->createTenant(code: $this->tenantCode('inactive-membership'));
        $user = $this->createUser();
        $tenantRole = $this->createRole($this->roleCode('tenant-user'), 'Tenant User');
        $this->grantTenantAccess($user, $tenant, $tenantRole, false);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/test/middleware/tenant-access', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertStatus(403);
    }

    public function test_it_allows_user_with_active_membership(): void
    {
        $tenant = $this->createTenant(code: $this->tenantCode('active-membership'));
        $user = $this->createUser();
        $tenantRole = $this->createRole($this->roleCode('tenant-user'), 'Tenant User');
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/test/middleware/tenant-access', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertOk()->assertJson([
            'success' => true,
        ]);
    }

    private function tenantCode(string $prefix): string
    {
        return $prefix.'-'.substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
    }

    private function roleCode(string $prefix): string
    {
        return $prefix.'-'.str_replace('-', '', (string) Str::uuid());
    }
}
