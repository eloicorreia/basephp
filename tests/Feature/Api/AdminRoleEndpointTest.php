<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminRoleEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_role_index_returns_active_roles_with_user_count(): void
    {
        $context = $this->createAdminContext();

        $activeRole = $this->createRole(
            'active-role-'.str_replace('-', '', (string) Str::uuid()),
            'Active Role'
        );
        $inactiveRole = $this->createRole(
            'inactive-role-'.str_replace('-', '', (string) Str::uuid()),
            'Inactive Role',
            false
        );

        $this->createUser(role: $activeRole);

        $response = $this->getJson('/api/v1/admin/roles', [
            'X-Tenant-Id' => $context['tenant']->code,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados recuperados com sucesso.');

        $codes = array_column($response->json('data'), 'code');

        $this->assertContains($activeRole->code, $codes);
        $this->assertNotContains($inactiveRole->code, $codes);

        $listedRole = collect($response->json('data'))
            ->firstWhere('code', $activeRole->code);

        $this->assertSame(1, $listedRole['users_count']);
    }

    public function test_admin_role_index_can_include_inactive_roles(): void
    {
        $context = $this->createAdminContext();

        $inactiveRole = $this->createRole(
            'inactive-visible-'.str_replace('-', '', (string) Str::uuid()),
            'Inactive Visible',
            false
        );

        $response = $this->getJson('/api/v1/admin/roles?active_only=false', [
            'X-Tenant-Id' => $context['tenant']->code,
        ]);

        $response->assertOk();

        $this->assertContains($inactiveRole->code, array_column($response->json('data'), 'code'));
    }

    public function test_admin_role_show_returns_role_data(): void
    {
        $context = $this->createAdminContext();

        $role = $this->createRole(
            'role-show-'.str_replace('-', '', (string) Str::uuid()),
            'Role Show'
        );

        $this->createUser(role: $role);

        $this->getJson('/api/v1/admin/roles/'.$role->id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $role->id)
            ->assertJsonPath('data.code', $role->code)
            ->assertJsonPath('data.users_count', 1);
    }

    public function test_admin_role_index_returns_forbidden_for_non_admin_user(): void
    {
        $context = $this->createNonAdminContext();

        $this->getJson('/api/v1/admin/roles', [
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
            code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid())
        );

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'admin'],
            [
                'name' => 'Administrator',
                'active' => true,
            ]
        );

        $tenantRole = $this->createRole(
            'tenant-admin-'.str_replace('-', '', (string) Str::uuid()),
            'Tenant Admin'
        );

        $user = $this->createUser(role: $adminRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

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
            code: 'tenant-user-'.str_replace('-', '', (string) Str::uuid())
        );

        $userRole = $this->createRole(
            'user-'.str_replace('-', '', (string) Str::uuid()),
            'User'
        );

        $tenantRole = $this->createRole(
            'tenant-user-role-'.str_replace('-', '', (string) Str::uuid()),
            'Tenant User'
        );

        $user = $this->createUser(role: $userRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        return [
            'tenant' => $tenant,
            'user' => $user,
        ];
    }
}
