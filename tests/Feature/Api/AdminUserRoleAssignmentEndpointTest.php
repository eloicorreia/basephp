<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminUserRoleAssignmentEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_can_assign_active_role_to_user(): void
    {
        $context = $this->createAdminContext();

        $oldRole = $this->createRole(
            'old-user-role-'.str_replace('-', '', (string) Str::uuid()),
            'Old User Role'
        );
        $newRole = $this->createRole(
            'new-user-role-'.str_replace('-', '', (string) Str::uuid()),
            'New User Role'
        );
        $targetUser = $this->createUser(role: $oldRole);

        $this->patchJson('/api/v1/admin/users/'.$targetUser->id.'/role', [
            'role_id' => $newRole->id,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Role do usuário atualizada com sucesso.')
            ->assertJsonPath('data.id', $targetUser->id)
            ->assertJsonPath('data.role.id', $newRole->id)
            ->assertJsonPath('data.role.code', $newRole->code);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'role_id' => $newRole->id,
        ]);

        $auditLog = AuditLog::query()
            ->where('action', 'user.role_assigned')
            ->where('auditable_id', $targetUser->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertSame($context['user']->id, $auditLog->user_id);
        $this->assertSame('admin', $auditLog->user_role);
        $this->assertSame([
            'role_id' => $oldRole->id,
            'role_code' => $oldRole->code,
            'role_name' => $oldRole->name,
        ], $auditLog->before_data);
        $this->assertSame([
            'role_id' => $newRole->id,
            'role_code' => $newRole->code,
            'role_name' => $newRole->name,
        ], $auditLog->after_data);
    }

    public function test_admin_cannot_assign_inactive_role_to_user(): void
    {
        $context = $this->createAdminContext();

        $currentRole = $this->createRole(
            'current-user-role-'.str_replace('-', '', (string) Str::uuid()),
            'Current User Role'
        );
        $inactiveRole = $this->createRole(
            'inactive-user-role-'.str_replace('-', '', (string) Str::uuid()),
            'Inactive User Role',
            false
        );
        $targetUser = $this->createUser(role: $currentRole);

        $this->patchJson('/api/v1/admin/users/'.$targetUser->id.'/role', [
            'role_id' => $inactiveRole->id,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'role_id' => $currentRole->id,
        ]);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $context = $this->createAdminContext();

        $backupAdmin = $this->createUser(role: $context['admin_role']);
        $this->grantTenantAccess($backupAdmin, $context['tenant'], $context['tenant_role'], true);

        $newRole = $this->createRole(
            'self-change-role-'.str_replace('-', '', (string) Str::uuid()),
            'Self Change Role'
        );

        $this->patchJson('/api/v1/admin/users/'.$context['user']->id.'/role', [
            'role_id' => $newRole->id,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', [
            'id' => $context['user']->id,
            'role_id' => $context['admin_role']->id,
        ]);
    }

    public function test_admin_cannot_remove_last_active_admin_role(): void
    {
        $context = $this->createAdminContext();

        $newRole = $this->createRole(
            'last-admin-target-role-'.str_replace('-', '', (string) Str::uuid()),
            'Last Admin Target Role'
        );

        $this->patchJson('/api/v1/admin/users/'.$context['user']->id.'/role', [
            'role_id' => $newRole->id,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', [
            'id' => $context['user']->id,
            'role_id' => $context['admin_role']->id,
        ]);
    }

    public function test_admin_user_store_rejects_inactive_role(): void
    {
        $context = $this->createAdminContext();

        $inactiveRole = $this->createRole(
            'inactive-store-role-'.str_replace('-', '', (string) Str::uuid()),
            'Inactive Store Role',
            false
        );

        $this->postJson('/api/v1/admin/users', [
            'name' => 'Usuário Inativo Role',
            'email' => 'inactive-role.'.str_replace('-', '', (string) Str::uuid()).'@example.com',
            'password' => 'SenhaForteMuitoBoa@123',
            'password_confirmation' => 'SenhaForteMuitoBoa@123',
            'role_id' => $inactiveRole->id,
            'is_active' => true,
            'must_change_password' => false,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_non_admin_cannot_assign_user_role(): void
    {
        $context = $this->createNonAdminContext();

        $targetRole = $this->createRole(
            'blocked-target-role-'.str_replace('-', '', (string) Str::uuid()),
            'Blocked Target Role'
        );
        $targetUser = $this->createUser();

        $this->patchJson('/api/v1/admin/users/'.$targetUser->id.'/role', [
            'role_id' => $targetRole->id,
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
            'tenant_role' => $tenantRole,
            'user' => $user,
            'admin_role' => $adminRole,
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
