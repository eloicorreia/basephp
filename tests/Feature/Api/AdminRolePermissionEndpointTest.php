<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminRolePermissionEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_can_list_permissions_catalog(): void
    {
        $context = $this->createAdminContext();

        $this->getJson('/api/v1/admin/permissions', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_admin_can_list_role_permissions(): void
    {
        $context = $this->createAdminContext();

        $role = $this->createRole(
            'role-permissions-'.str_replace('-', '', (string) Str::uuid()),
            'Role Permissions'
        );
        $permission = Permission::query()
            ->where('code', PermissionRegistry::USERS_READ)
            ->firstOrFail();

        $role->permissions()->sync([$permission->id]);

        $this->getJson('/api/v1/admin/roles/'.$role->id.'/permissions', [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('data.0.code', PermissionRegistry::USERS_READ);
    }

    public function test_admin_can_sync_role_permissions_and_audit_change(): void
    {
        $context = $this->createAdminContext();

        $role = $this->createRole(
            'sync-role-permissions-'.str_replace('-', '', (string) Str::uuid()),
            'Sync Role Permissions'
        );
        $usersRead = Permission::query()
            ->where('code', PermissionRegistry::USERS_READ)
            ->firstOrFail();
        $usersWrite = Permission::query()
            ->where('code', PermissionRegistry::USERS_WRITE)
            ->firstOrFail();

        $role->permissions()->sync([$usersRead->id]);

        $this->putJson('/api/v1/admin/roles/'.$role->id.'/permissions', [
            'permission_ids' => [$usersWrite->id],
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Permissões da role atualizadas com sucesso.')
            ->assertJsonPath('data.0.code', PermissionRegistry::USERS_WRITE);

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $usersWrite->id,
            'assigned_by' => $context['user']->id,
        ]);
        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $usersRead->id,
        ]);

        $auditLog = AuditLog::query()
            ->where('action', 'role.permissions_synced')
            ->where('auditable_id', $role->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertSame($context['user']->id, $auditLog->user_id);
        $this->assertSame('admin', $auditLog->user_role);
        $this->assertSame(PermissionRegistry::USERS_READ, $auditLog->before_data['permissions'][0]['code']);
        $this->assertSame($usersRead->name, $auditLog->before_data['permissions'][0]['name']);
        $this->assertSame($usersRead->context, $auditLog->before_data['permissions'][0]['context']);
        $this->assertSame($usersRead->is_sensitive, $auditLog->before_data['permissions'][0]['is_sensitive']);
        $this->assertSame(PermissionRegistry::USERS_WRITE, $auditLog->after_data['permissions'][0]['code']);
        $this->assertSame($usersWrite->name, $auditLog->after_data['permissions'][0]['name']);
    }

    public function test_sync_role_permissions_rejects_inactive_permission(): void
    {
        $context = $this->createAdminContext();

        $role = $this->createRole(
            'sync-inactive-permission-'.str_replace('-', '', (string) Str::uuid()),
            'Sync Inactive Permission'
        );
        $permission = Permission::query()
            ->where('code', PermissionRegistry::USERS_READ)
            ->firstOrFail();
        $permission->forceFill(['active' => false])->save();

        $this->putJson('/api/v1/admin/roles/'.$role->id.'/permissions', [
            'permission_ids' => [$permission->id],
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_sync_role_permissions_cannot_remove_admin_full_from_last_operational_role(): void
    {
        $context = $this->createAdminContext();

        $usersRead = Permission::query()->where('code', PermissionRegistry::USERS_READ)->firstOrFail();

        $this->putJson('/api/v1/admin/roles/'.$context['admin_role']->id.'/permissions', [
            'permission_ids' => [$usersRead->id],
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_sync_role_permissions_cannot_remove_roles_write_from_all_roles(): void
    {
        $context = $this->createAdminContext();

        $adminFull = Permission::query()->where('code', PermissionRegistry::ADMIN_FULL)->firstOrFail();
        $permissionsWrite = Permission::query()->where('code', PermissionRegistry::PERMISSIONS_WRITE)->firstOrFail();

        $this->putJson('/api/v1/admin/roles/'.$context['admin_role']->id.'/permissions', [
            'permission_ids' => [$adminFull->id, $permissionsWrite->id],
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_sync_role_permissions_cannot_leave_system_without_active_permission_admin_user(): void
    {
        $context = $this->createAdminContext();

        $backupRole = $this->createRole(
            'permission-admin-backup-'.str_replace('-', '', (string) Str::uuid()),
            'Permission Admin Backup'
        );
        $adminFull = Permission::query()->where('code', PermissionRegistry::ADMIN_FULL)->firstOrFail();
        $rolesWrite = Permission::query()->where('code', PermissionRegistry::ROLES_WRITE)->firstOrFail();
        $permissionsWrite = Permission::query()->where('code', PermissionRegistry::PERMISSIONS_WRITE)->firstOrFail();
        $usersRead = Permission::query()->where('code', PermissionRegistry::USERS_READ)->firstOrFail();

        $backupRole->permissions()->sync([$adminFull->id, $rolesWrite->id, $permissionsWrite->id]);

        $this->putJson('/api/v1/admin/roles/'.$context['admin_role']->id.'/permissions', [
            'permission_ids' => [$usersRead->id],
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_sync_role_permissions_cannot_remove_own_permission_administration_access(): void
    {
        $context = $this->createAdminContext();

        $backupRole = $this->createRole(
            'perm-admin-user-'.str_replace('-', '', (string) Str::uuid()),
            'Permission Admin User Backup'
        );
        $adminFull = Permission::query()->where('code', PermissionRegistry::ADMIN_FULL)->firstOrFail();
        $rolesWrite = Permission::query()->where('code', PermissionRegistry::ROLES_WRITE)->firstOrFail();
        $permissionsWrite = Permission::query()->where('code', PermissionRegistry::PERMISSIONS_WRITE)->firstOrFail();
        $usersRead = Permission::query()->where('code', PermissionRegistry::USERS_READ)->firstOrFail();

        $backupRole->permissions()->sync([$adminFull->id, $rolesWrite->id, $permissionsWrite->id]);
        $backupUser = $this->createUser(role: $backupRole);
        $this->grantTenantAccess($backupUser, $context['tenant'], $context['tenant_role'], true);

        $this->putJson('/api/v1/admin/roles/'.$context['admin_role']->id.'/permissions', [
            'permission_ids' => [$usersRead->id],
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(422)
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
}
