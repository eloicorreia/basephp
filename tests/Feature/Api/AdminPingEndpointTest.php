<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminPingEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_ping_returns_success_for_authorized_user_when_contract_requires_it(): void
    {
        $tenant = $this->createTenant(
            code: 'tenant-main-' . str_replace('-', '', (string) Str::uuid())
        );

        $adminRole = \App\Models\Role::query()->firstOrCreate(
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

        $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Operação realizada com sucesso.')
            ->assertJsonPath('data.area', 'admin');
    }

    public function test_admin_ping_returns_forbidden_for_non_admin_user(): void
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

        $this->getJson('/api/v1/admin/ping', [
            'X-Tenant-Id' => $tenant->code,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}