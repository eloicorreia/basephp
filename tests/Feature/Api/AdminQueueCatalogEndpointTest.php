<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminQueueCatalogEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_queue_catalog_returns_available_queues_for_admin(): void
    {
        $context = $this->createAdminContext();

        $response = $this->getJson('/api/v1/admin/queues/catalog', [
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

    public function test_queue_catalog_returns_forbidden_for_non_admin(): void
    {
        $context = $this->createNonAdminContext();

        $this->getJson('/api/v1/admin/queues/catalog', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertStatus(403);
    }

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

        return ['tenant' => $tenant, 'user' => $user];
    }

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

        return ['tenant' => $tenant, 'user' => $user];
    }
}