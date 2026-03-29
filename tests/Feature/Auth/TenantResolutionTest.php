<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

class TenantResolutionTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    use RefreshDatabase;

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
            'X-Tenant-Id' => 'tenant-inexistente',
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
            code: 'tenant-inactive',
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
        $tenant = $this->createTenant(code: 'tenant-main');
        $user = $this->createUser();

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Usuário sem acesso ao tenant informado.',
                'errors' => [],
            ]);
    }

    public function test_it_rejects_inactive_user_tenant_membership(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $user = $this->createUser();
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');

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
                'message' => 'Usuário sem acesso ao tenant informado.',
                'errors' => [],
            ]);
    }

    public function test_it_allows_access_when_user_has_active_membership(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $userRole = $this->createRole('user', 'User');
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');
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
        $tenant = $this->createTenant(code: 'tenant-main');
        $user = $this->createUser(isActive: false);
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Usuário inativo.',
                'errors' => [],
            ]);
    }

    public function test_it_blocks_user_that_must_change_password(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $user = $this->createUser(mustChangePassword: true);
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/auth/me', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'É obrigatório alterar a senha antes de continuar.',
                'errors' => [],
            ]);
    }

    public function test_admin_route_requires_admin_role(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $userRole = $this->createRole('user', 'User');
        $tenantRole = $this->createRole('tenant-user', 'Tenant User');
        $user = $this->createUser(role: $userRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Acesso negado para este perfil.',
                'errors' => [],
            ]);
    }

    public function test_admin_route_allows_admin_role(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main');
        $adminRole = $this->createRole('admin', 'Administrator');
        $tenantRole = $this->createRole('tenant-admin', 'Tenant Admin');
        $user = $this->createUser(role: $adminRole);

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        $response = $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Operação realizada com sucesso.',
                'data' => [
                    'area' => 'admin',
                ],
            ]);
    }
}