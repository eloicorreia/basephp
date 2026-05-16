<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminPermissionEndpointTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_can_create_permission(): void
    {
        $context = $this->createAdminContext();
        $code = 'custom.permission.'.str_replace('-', '', (string) Str::uuid());

        $this->postJson('/api/v1/admin/permissions', [
            'code' => $code,
            'name' => 'Custom Permission',
            'description' => 'Custom permission for tests.',
            'group' => 'Custom',
            'context' => 'api',
            'is_sensitive' => true,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', $code)
            ->assertJsonPath('data.group', 'Custom')
            ->assertJsonPath('data.is_system', false);

        $this->assertDatabaseHas('permissions', [
            'code' => $code,
            'group' => 'Custom',
            'is_system' => false,
            'active' => true,
        ]);
    }

    public function test_admin_can_update_custom_permission(): void
    {
        $context = $this->createAdminContext();
        $permission = Permission::query()->create([
            'code' => 'custom.update.'.str_replace('-', '', (string) Str::uuid()),
            'name' => 'Old Name',
            'description' => 'Old description.',
            'group' => 'Old',
            'context' => 'api',
            'is_system' => false,
            'is_sensitive' => false,
            'active' => true,
        ]);

        $this->putJson('/api/v1/admin/permissions/'.$permission->id, [
            'name' => 'New Name',
            'description' => 'New description.',
            'group' => 'New',
            'context' => 'web',
            'is_sensitive' => true,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.context', 'web')
            ->assertJsonPath('data.is_sensitive', true);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'New Name',
            'context' => 'web',
            'is_sensitive' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'permission.updated',
            'auditable_id' => $permission->id,
        ]);
    }

    public function test_admin_can_enable_and_disable_custom_permission_without_physical_delete(): void
    {
        $context = $this->createAdminContext();
        $permission = Permission::query()->create([
            'code' => 'custom.toggle.'.str_replace('-', '', (string) Str::uuid()),
            'name' => 'Toggle Permission',
            'description' => null,
            'group' => 'Custom',
            'context' => 'api',
            'is_system' => false,
            'is_sensitive' => false,
            'active' => true,
        ]);

        $this->patchJson('/api/v1/admin/permissions/'.$permission->id.'/disable', [], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('data.active', false);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'active' => false,
        ]);

        $this->patchJson('/api/v1/admin/permissions/'.$permission->id.'/enable', [], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('data.active', true);
    }

    public function test_admin_cannot_disable_system_permission(): void
    {
        $context = $this->createAdminContext();
        $permission = Permission::query()
            ->where('code', PermissionRegistry::PERMISSIONS_WRITE)
            ->firstOrFail();

        $this->patchJson('/api/v1/admin/permissions/'.$permission->id.'/disable', [], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'active' => true,
        ]);
    }

    public function test_system_permission_update_keeps_security_metadata(): void
    {
        $context = $this->createAdminContext();
        $permission = Permission::query()
            ->where('code', PermissionRegistry::PERMISSIONS_WRITE)
            ->firstOrFail();

        $this->putJson('/api/v1/admin/permissions/'.$permission->id, [
            'name' => 'Novo nome permitido',
            'description' => 'Nova descrição permitida.',
            'group' => 'Governança',
            'context' => 'web',
            'is_sensitive' => false,
        ], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Novo nome permitido')
            ->assertJsonPath('data.context', $permission->context)
            ->assertJsonPath('data.is_sensitive', $permission->is_sensitive);
    }

    public function test_permission_id_restricts_delete_when_permission_is_assigned_to_role(): void
    {
        $this->createAdminContext();
        $permission = Permission::query()
            ->where('code', PermissionRegistry::USERS_READ)
            ->firstOrFail();

        $this->expectException(QueryException::class);

        $permission->delete();
    }

    public function test_permission_audit_snapshot_contains_governance_fields(): void
    {
        $context = $this->createAdminContext();
        $permission = Permission::query()->create([
            'code' => 'custom.audit.'.str_replace('-', '', (string) Str::uuid()),
            'name' => 'Audit Permission',
            'description' => 'Audit description.',
            'group' => 'Audit',
            'context' => 'api',
            'is_system' => false,
            'is_sensitive' => false,
            'active' => true,
        ]);

        $this->patchJson('/api/v1/admin/permissions/'.$permission->id.'/disable', [], [
            'X-Tenant-Id' => $context['tenant']->code,
        ])->assertOk();

        $auditLog = AuditLog::query()
            ->where('action', 'permission.disabled')
            ->where('auditable_id', $permission->id)
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertSame($permission->code, $auditLog->before_data['code']);
        $this->assertSame('Audit', $auditLog->before_data['group']);
        $this->assertSame(false, $auditLog->before_data['is_system']);
        $this->assertSame(false, $auditLog->before_data['is_sensitive']);
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
