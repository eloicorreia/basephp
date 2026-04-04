<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminTenantUserEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_tenant_user_index_returns_paginated_data_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $targetTenant = $this->createTenant(
            code: 'tenant-link-' . str_replace('-', '', (string) Str::uuid())
        );

        $targetUserRole = $this->createRole(
            'linked-user-' . str_replace('-', '', (string) Str::uuid()),
            'Linked User'
        );

        $targetTenantRole = $this->createRole(
            'linked-tenant-role-' . str_replace('-', '', (string) Str::uuid()),
            'Linked Tenant Role'
        );

        $targetUser = $this->createUser(role: $targetUserRole);

        $this->grantTenantAccess($targetUser, $targetTenant, $targetTenantRole, true);

        $response = $this->getJson('/api/v1/admin/tenant-users', [
            'X-Tenant-Id' => $context['tenant']->code,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados recuperados com sucesso.');

        $data = $response->json('data');

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function test_admin_tenant_user_store_creates_or_updates_link(): void
    {
        $context = $this->createAdminContext();

        $targetTenant = $this->createTenant(
            code: 'tenant-link-store-' . str_replace('-', '', (string) Str::uuid())
        );

        $targetUserRole = $this->createRole(
            'store-user-' . str_replace('-', '', (string) Str::uuid()),
            'Store User'
        );

        $targetTenantRole = $this->createRole(
            'store-tenant-role-' . str_replace('-', '', (string) Str::uuid()),
            'Store Tenant Role'
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
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Vínculo salvo com sucesso.')
            ->assertJsonPath('data.tenant.id', $targetTenant->id)
            ->assertJsonPath('data.user.id', $targetUser->id);

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

        $this->postJson('/api/v1/admin/tenant-users', [
            'tenant_id' => 999999999,
            'user_id' => 999999999,
            'role_id' => 999999999,
            'is_active' => 'invalido',
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertStatus(422);
    }

    /**
     * @return array<string, mixed>
     */
    private function createAdminContext(): array
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

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
    }
}