<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminUserShowEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_user_show_returns_user_data_for_admin_user(): void
    {
        $context = $this->createAdminContext();

        $targetRole = $this->createRole(
            'user-show-' . str_replace('-', '', (string) Str::uuid()),
            'Show User'
        );

        $targetUser = $this->createUser(role: $targetRole);

        $this->getJson('/api/v1/admin/users/' . $targetUser->id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Dados recuperados com sucesso.')
            ->assertJsonPath('data.id', $targetUser->id)
            ->assertJsonPath('data.email', $targetUser->email)
            ->assertJsonPath('data.role.id', $targetRole->id);
    }

    public function test_admin_user_show_returns_forbidden_for_non_admin_user(): void
    {
        $context = $this->createNonAdminContext();

        $targetRole = $this->createRole(
            'user-show-blocked-' . str_replace('-', '', (string) Str::uuid()),
            'Blocked Show User'
        );

        $targetUser = $this->createUser(role: $targetRole);

        $this->getJson('/api/v1/admin/users/' . $targetUser->id, [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_user_show_returns_not_found_for_missing_user(): void
    {
        $context = $this->createAdminContext();

        $this->getJson('/api/v1/admin/users/999999999', [
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