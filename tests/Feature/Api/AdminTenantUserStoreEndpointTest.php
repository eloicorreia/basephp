<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use App\DTO\Admin\CreateTenantUserDTO;
use App\Http\Resources\Api\V1\TenantUserResource;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminTenantUserStoreEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_tenant_user_store_creates_link_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $targetTenant = $this->createTenant(
            code: 'tlink-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        $targetUserRole = $this->createRole(
            'store-user-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            'Store User'
        );

        $targetTenantRole = $this->createRole(
            'store-tenant-role-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            'Store Tenant Role'
        );

        $targetUser = $this->createUser(role: $targetUserRole);

        $this->assertSame('admin', $context['user']->role->code);

        $this->postJson('/api/v1/admin/tenant-users', [
            'tenant_id' => $targetTenant->id,
            'user_id' => $targetUser->id,
            'role_id' => $targetTenantRole->id,
            'is_active' => true,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Vínculo salvo com sucesso.')
            ->assertJsonPath('data.tenant.id', $targetTenant->id)
            ->assertJsonPath('data.user.id', $targetUser->id)
            ->assertJsonPath('data.role.id', $targetTenantRole->id);

        $this->assertDatabaseHas('tenant_users', [
            'tenant_id' => $targetTenant->id,
            'user_id' => $targetUser->id,
            'role_id' => $targetTenantRole->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_tenant_user_store_returns_validation_error_for_invalid_payload(): void
    {
        $context = $this->createAdminContext();

        $this->assertSame('admin', $context['user']->role->code);

        $this->postJson('/api/v1/admin/tenant-users', [
            'tenant_id' => 999999999,
            'user_id' => 999999999,
            'role_id' => 999999999,
            'is_active' => 'invalido',
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertStatus(422);
    }

    public function test_admin_tenant_user_store_returns_forbidden_for_non_admin_user(): void
    {
        $context = $this->createNonAdminContext();

        $targetTenant = $this->createTenant(
            code: 'tblk-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        $targetUserRole = $this->createRole(
            'blocked-user-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            'Blocked User'
        );

        $targetTenantRole = $this->createRole(
            'blocked-role-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            'Blocked Tenant Role'
        );

        $targetUser = $this->createUser(role: $targetUserRole);

        $this->postJson('/api/v1/admin/tenant-users', [
            'tenant_id' => $targetTenant->id,
            'user_id' => $targetUser->id,
            'role_id' => $targetTenantRole->id,
            'is_active' => true,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function createAdminContext(): array
    {
        $tenant = $this->createTenant(
            code: 'tmain-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'admin'],
            [
                'name' => 'Administrator',
                'active' => true,
            ]
        );

        $tenantRole = $this->createRole(
            'tenant-admin-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            'Tenant Admin'
        );

        $user = $this->createUser(role: $adminRole);
        $user->load('role');

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createNonAdminContext(): array
    {
        $tenant = $this->createTenant(
            code: 'tuser-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        $userRole = $this->createRole(
            'user-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            'User'
        );

        $tenantRole = $this->createRole(
            'tenant-user-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            'Tenant User'
        );

        $user = $this->createUser(role: $userRole);
        $user->load('role');

        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
    }
}