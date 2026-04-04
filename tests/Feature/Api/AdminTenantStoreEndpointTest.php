<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminTenantStoreEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_tenant_store_creates_tenant_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $code = 'tenant-new-' . str_replace('-', '', (string) Str::uuid());
        $schemaName = 'tenant_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 20);

        $this->postJson('/api/v1/admin/tenants', [
            'code' => $code,
            'name' => 'Tenant Novo',
            'schema_name' => $schemaName,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Tenant provisionado com sucesso.')
            ->assertJsonPath('data.code', $code)
            ->assertJsonPath('data.name', 'Tenant Novo')
            ->assertJsonPath('data.schema_name', $schemaName);

        $this->assertDatabaseHas('tenants', [
            'code' => $code,
            'name' => 'Tenant Novo',
            'schema_name' => $schemaName,
        ]);
    }

    public function test_admin_tenant_store_returns_validation_error_for_invalid_payload(): void
    {
        $context = $this->createAdminContext();

        $this->postJson('/api/v1/admin/tenants', [
            'code' => '',
            'name' => '',
            'schema_name' => '',
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertStatus(422);
    }

    public function test_admin_tenant_store_returns_forbidden_for_non_admin_user(): void
    {
        $context = $this->createNonAdminContext();

        $this->postJson('/api/v1/admin/tenants', [
            'code' => 'tenant-blocked-' . str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant Bloqueado',
            'schema_name' => 'tenant_blocked_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 10),
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