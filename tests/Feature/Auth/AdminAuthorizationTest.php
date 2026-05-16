<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminAuthorizationTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_route_requires_admin_role(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $userRole = $this->createRole('user-'.str_replace('-', '', (string) Str::uuid()), 'User');
        $tenantRole = $this->createRole('tenant-user-'.str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $user = $this->createUser(role: $userRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);
        $this->getJson('/api/v1/admin/ping', ['X-Tenant-Id' => $tenant->code])->assertStatus(403);
    }

    public function test_admin_route_allows_admin_role_when_contract_requires_it(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $adminRole = $this->createRole('admin', 'Admin');
        $tenantRole = $this->createRole('tenant-admin-'.str_replace('-', '', (string) Str::uuid()), 'Tenant Admin');
        $user = $this->createUser(role: $adminRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertOk()->assertJson([
            'success' => true,
            'data' => ['area' => 'admin'],
        ]);
    }

    public function test_admin_route_rejects_inactive_admin_role(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $adminRole = $this->createRole('admin', 'Admin', false);
        $tenantRole = $this->createRole('tenant-admin-'.str_replace('-', '', (string) Str::uuid()), 'Tenant Admin');
        $user = $this->createUser(role: $adminRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Acesso negado.',
            'errors' => [],
        ]);
    }

    public function test_admin_route_requires_admin_oauth_scope(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $adminRole = $this->createRole('admin', 'Admin');
        $tenantRole = $this->createRole('tenant-admin-'.str_replace('-', '', (string) Str::uuid()), 'Tenant Admin');
        $user = $this->createUser(role: $adminRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile', 'tenant.access']);

        $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Acesso negado.',
            'errors' => [],
        ]);
    }

    public function test_admin_route_returns_standard_forbidden_response_for_non_admin_user(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $userRole = $this->createRole('user-'.str_replace('-', '', (string) Str::uuid()), 'User');
        $tenantRole = $this->createRole('tenant-user-'.str_replace('-', '', (string) Str::uuid()), 'Tenant User');
        $user = $this->createUser(role: $userRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertStatus(403)->assertJson([
            'success' => false,
            'message' => 'Acesso negado.',
            'errors' => [],
        ]);
    }
}
