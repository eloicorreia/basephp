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
        $this->assertSame(PermissionRegistry::USERS_WRITE, $auditLog->after_data['permissions'][0]['code']);
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
}
