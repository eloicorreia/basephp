<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Permission;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class DynamicPermissionAuthorizationTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_admin_route_requires_database_permission(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $adminRole = $this->createRole('admin', 'Admin');
        $tenantRole = $this->createRole('tenant-admin-'.str_replace('-', '', (string) Str::uuid()), 'Tenant Admin');
        $user = $this->createUser(role: $adminRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);
        $adminRole->permissions()->detach();

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/admin/users', [
            'X-Tenant-Id' => $tenant->code,
        ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_route_allows_specific_database_permission(): void
    {
        $tenant = $this->createTenant(code: 'tenant-main-'.str_replace('-', '', (string) Str::uuid()));
        $adminRole = $this->createRole('admin', 'Admin');
        $tenantRole = $this->createRole('tenant-admin-'.str_replace('-', '', (string) Str::uuid()), 'Tenant Admin');
        $user = $this->createUser(role: $adminRole);
        $this->grantTenantAccess($user, $tenant, $tenantRole, true);

        $permission = Permission::query()
            ->where('code', PermissionRegistry::USERS_READ)
            ->firstOrFail();

        $adminRole->permissions()->sync([$permission->id]);

        Passport::actingAs($user, ['user.profile', 'tenant.access', 'admin.full', 'user.password.change']);

        $this->getJson('/api/v1/admin/users', [
            'X-Tenant-Id' => $tenant->code,
        ])->assertOk();
    }
}
