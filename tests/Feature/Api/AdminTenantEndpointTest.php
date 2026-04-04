<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminTenantEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_tenant_index_returns_paginated_data_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $tenantOne = $this->createTenant(
            code: 'tenant-a-' . str_replace('-', '', (string) Str::uuid())
        );

        $tenantTwo = $this->createTenant(
            code: 'tenant-b-' . str_replace('-', '', (string) Str::uuid())
        );

        $response = $this->getJson('/api/v1/admin/tenants', [
            'X-Tenant-Id' => $context['tenant']->code,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados recuperados com sucesso.');

        $data = $response->json('data');

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertContains($tenantOne->code, array_column($data, 'code'));
        $this->assertContains($tenantTwo->code, array_column($data, 'code'));
    }

    public function test_admin_tenant_store_creates_tenant_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $code = 'tenant-new-' . str_replace('-', '', (string) Str::uuid());

        $this->postJson('/api/v1/admin/tenants', [
            'code' => $code,
            'name' => 'Tenant Novo',
            'schema_name' => 'tenant_novo_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Tenant provisionado com sucesso.')
            ->assertJsonPath('data.code', $code);

        $this->assertDatabaseHas('tenants', [
            'code' => $code,
            'name' => 'Tenant Novo',
        ]);
    }

    public function test_admin_tenant_show_returns_tenant_data(): void
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
            ->assertJsonPath('data.id', $targetTenant->id)
            ->assertJsonPath('data.code', $targetTenant->code);
    }

    public function test_admin_tenant_store_returns_validation_error_for_invalid_payload(): void
    {
        $context = $this->createAdminContext();

        $this->postJson('/api/v1/admin/tenants', [
            'code' => 'INVALID CODE',
            'name' => '',
            'schema_name' => '1',
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