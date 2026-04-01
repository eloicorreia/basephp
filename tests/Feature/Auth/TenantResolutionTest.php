<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantResolutionTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_it_requires_tenant_header(): void
    {
        $user = $this->createUser();

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Tenant não informado.',
                'errors' => [],
            ]);
    }

    public function test_it_rejects_unknown_tenant_code(): void
    {
        $user = $this->createUser();

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => 'tenant-inexistente-' . str_replace('-', '', (string) Str::uuid()),
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tenant não encontrado.',
                'errors' => [],
            ]);
    }

    public function test_it_rejects_inactive_tenant(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-inactive-' . str_replace('-', '', (string) Str::uuid()),
            status: 'inactive'
        );

        $user = $this->createUser();

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Tenant não encontrado.',
                'errors' => [],
            ]);
    }

    public function test_it_rejects_user_without_access_to_tenant(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $user = $this->createUser();

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }

    public function test_it_rejects_inactive_user_tenant_membership(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $user = $this->createUser();

        $tenantRole = $this->createRole(
            'tenant-user-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $this->grantTenantAccess(
            user: $user,
            tenant: $tenant,
            role: $tenantRole,
            isActive: false
        );

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }

    public function test_it_allows_access_when_user_has_active_membership(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $userRole = $this->createRole(
            'user-' . str_replace('-', '', (string) Str::uuid()),
            'User'
        );

        $tenantRole = $this->createRole(
            'tenant-user-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $user = $this->createUser(role: $userRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Dados recuperados com sucesso.',
            ]);
    }

    public function test_it_blocks_user_marked_as_inactive(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $user = $this->createUser(isActive: false);

        $tenantRole = $this->createRole(
            'tenant-user-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }

    public function test_it_blocks_user_that_must_change_password(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $user = $this->createUser(mustChangePassword: true);

        $tenantRole = $this->createRole(
            'tenant-user-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }

    public function test_admin_route_requires_admin_role(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $userRole = $this->createRole(
            'user-' . str_replace('-', '', (string) Str::uuid()),
            'User'
        );

        $tenantRole = $this->createRole(
            'tenant-user-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $user = $this->createUser(role: $userRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }

    public function test_admin_route_allows_admin_role(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $adminRole = $this->createRole(
            'admin-' . str_replace('-', '', (string) Str::uuid()),
            'Administrator'
        );

        $tenantRole = $this->createRole(
            'tenant-admin-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant Admin'
        );

        $user = $this->createUser(role: $adminRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado.',
                'errors' => [],
            ]);
    }
    }