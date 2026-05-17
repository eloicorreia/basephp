<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\Enums\RoleCode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Web\WebAdminPermissions;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminSecurityWebTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_can_view_security_tree(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user, 'web')
            ->get(route('admin.security.index'))
            ->assertOk()
            ->assertSee('Árvore de segurança')
            ->assertSee('Usuários')
            ->assertSee('Roles')
            ->assertSee('Permissões');
    }

    public function test_user_without_security_permission_cannot_view_security_module(): void
    {
        $role = $this->createRole('web-access-only', 'Web Access Only');
        $access = Permission::query()->where('code', WebAdminPermissions::ACCESS)->firstOrFail();
        $role->permissions()->sync([$access->id => ['assigned_at' => now()]]);
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web')
            ->get(route('admin.security.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_security_role_and_assign_permission(): void
    {
        $user = $this->adminUser();
        $permission = Permission::query()->where('code', WebAdminPermissions::SECURITY_VIEW)->firstOrFail();

        $response = $this->actingAs($user, 'web')->post(route('admin.security.roles.store'), [
            'code' => 'security.viewer',
            'name' => 'Security Viewer',
            'active' => '1',
        ]);

        $role = Role::query()->where('code', 'security.viewer')->firstOrFail();
        $response->assertRedirect(route('admin.security.roles.edit', $role));

        $this->actingAs($user, 'web')
            ->put(route('admin.security.roles.update', $role), [
                'name' => 'Security Viewer',
                'active' => '1',
                'permission_ids' => [$permission->id],
            ])
            ->assertRedirect(route('admin.security.roles.edit', $role));

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_admin_can_create_user_from_security_screen(): void
    {
        $user = $this->adminUser();
        $role = Role::query()->where('code', RoleCode::ADMIN->value)->firstOrFail();

        $this->actingAs($user, 'web')
            ->post(route('admin.security.users.store'), [
                'name' => 'Security Created User',
                'email' => 'security-created@example.com',
                'password' => 'temporary-password',
                'password_confirmation' => 'temporary-password',
                'role_id' => $role->id,
                'is_active' => '1',
                'must_change_password' => '1',
            ])
            ->assertRedirect(route('admin.security.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'security-created@example.com',
            'role_id' => $role->id,
            'is_active' => true,
            'must_change_password' => true,
        ]);
    }

    public function test_admin_can_create_custom_permission_from_security_screen(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user, 'web')
            ->post(route('admin.security.permissions.store'), [
                'code' => 'custom.security.audit',
                'name' => 'Custom Security Audit',
                'description' => 'Custom permission managed by web.',
                'group' => 'Segurança',
                'context' => 'web',
                'is_sensitive' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('permissions', [
            'code' => 'custom.security.audit',
            'context' => 'web',
            'is_sensitive' => true,
        ]);
    }

    private function adminUser(): User
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');

        return $this->createUser(role: $role);
    }
}
