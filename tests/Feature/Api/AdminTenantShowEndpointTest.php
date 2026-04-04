<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminTenantShowEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_tenant_show_returns_tenant_data_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $targetTenant = $this->createTenant(
            code: 'tenant-show-' . str_replace('-', '', (string) Str::uuid())
        );

        $this->getJson('/api/v1/admin/tenants/' . $targetTenant->id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados recuperados com sucesso.')
            ->assertJsonPath('data.id', $targetTenant->id)
            ->assertJsonPath('data.code', $targetTenant->code)
            ->assertJsonPath('data.name', $targetTenant->name)
            ->assertJsonPath('data.schema_name', $targetTenant->schema_name);
    }

    public function test_admin_tenant_show_returns_forbidden_for_non_admin_user(): void
    {
        $context = $this->createNonAdminContext();

        $targetTenant = $this->createTenant(
            code: 'tenant-show-' . str_replace('-', '', (string) Str::uuid())
        );

        $this->getJson('/api/v1/admin/tenants/' . $targetTenant->id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_tenant_show_returns_not_found_for_missing_tenant(): void
    {
        $context = $this->createAdminContext();

        $this->getJson('/api/v1/admin/tenants/999999999', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertStatus(404);
    }

    /**
     * @return array<string, mixed>
     */
    private function createAdminContext(): array
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'admin'],
            [
                'name' => 'Administrator',
                'active' => true,
            ]
        );

        $tenantRole = $this->createRole(
            'tenant-admin-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant Admin'
        );

        $user = $this->createUser(role: $adminRole);
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
            code: 'tenant-user-' . str_replace('-', '', (string) Str::uuid())
        );

        $userRole = $this->createRole(
            'user-' . str_replace('-', '', (string) Str::uuid()),
            'User'
        );

        $tenantRole = $this->createRole(
            'tenant-user-role-' . str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $user = $this->createUser(role: $userRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile']);

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
    }
}