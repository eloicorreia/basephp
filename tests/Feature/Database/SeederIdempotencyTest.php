<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\AdminMenuGroup;
use App\Models\AdminMenuItem;
use App\Models\AdminMenuVersion;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Auth\PermissionRegistry;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_runs_twice_without_duplicating_bootstrap_records(): void
    {
        config()->set('bootstrap.admin_user.email', 'admin@example.com');
        config()->set('bootstrap.admin_user.password', 'ChangeMe123!');
        config()->set('bootstrap.development_tenant.enabled', true);

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@example.com')->count());
        $this->assertNoDuplicateCodes(Permission::class);
        $this->assertNoDuplicateCodes(Role::class);
        $this->assertNoDuplicateCodes(AdminMenuGroup::class);
        $this->assertNoDuplicateCodes(AdminMenuItem::class);
    }

    public function test_admin_role_receives_admin_full_without_removing_existing_permissions(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $adminRole = Role::query()->where('code', 'admin')->firstOrFail();

        $this->assertTrue(
            $adminRole->permissions()
                ->where('permissions.code', PermissionRegistry::ADMIN_FULL)
                ->exists()
        );

        $this->assertSame(1, Role::query()->where('code', 'admin')->count());
    }

    public function test_admin_user_seeder_does_not_overwrite_existing_password(): void
    {
        config()->set('bootstrap.admin_user.email', 'admin@example.com');
        config()->set('bootstrap.admin_user.password', 'NewPassword123!');

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $adminRole = Role::query()->where('code', 'admin')->firstOrFail();

        User::query()->create([
            'name' => 'Administrador Existente',
            'email' => 'admin@example.com',
            'password' => Hash::make('OriginalPassword123!'),
            'role_id' => $adminRole->id,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->seed(AdminUserSeeder::class);

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('OriginalPassword123!', $user->password));
        $this->assertFalse((bool) $user->must_change_password);
    }

    public function test_admin_user_seeder_links_admin_role_when_existing_user_has_no_role(): void
    {
        config()->set('bootstrap.admin_user.email', 'admin@example.com');
        config()->set('bootstrap.admin_user.password', 'ChangeMe123!');

        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        User::query()->create([
            'name' => 'Administrador Existente',
            'email' => 'admin@example.com',
            'password' => Hash::make('OriginalPassword123!'),
            'role_id' => null,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $this->seed(AdminUserSeeder::class);

        $adminRole = Role::query()->where('code', 'admin')->firstOrFail();
        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame((int) $adminRole->id, (int) $user->role_id);
        $this->assertTrue(Hash::check('OriginalPassword123!', $user->password));
    }

    public function test_tenant_seeder_creates_development_tenant_when_enabled(): void
    {
        config()->set('bootstrap.development_tenant.enabled', true);

        $this->seed(TenantSeeder::class);
        $this->seed(TenantSeeder::class);

        $this->assertSame(1, Tenant::query()->where('code', 'tenant-dev-001')->count());
        $this->assertDatabaseHas('tenants', [
            'code' => 'tenant-dev-001',
            'schema_name' => 'tenant_dev_001',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
    }

    public function test_tenant_seeder_skips_development_tenant_in_production_without_explicit_permission(): void
    {
        $originalEnvironment = app()->environment();

        try {
            app()->detectEnvironment(static fn (): string => 'production');
            config()->set('bootstrap.development_tenant.enabled', false);

            $this->seed(TenantSeeder::class);

            $this->assertSame(0, Tenant::query()->where('code', 'tenant-dev-001')->count());
        } finally {
            app()->detectEnvironment(static fn (): string => $originalEnvironment);
        }
    }

    public function test_admin_menu_seeder_increments_version_only_when_there_is_real_change(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->seed(AdminMenuSeeder::class);

        $versionAfterFirstRun = AdminMenuVersion::query()->whereKey(1)->value('version');

        $this->seed(AdminMenuSeeder::class);

        $versionAfterSecondRun = AdminMenuVersion::query()->whereKey(1)->value('version');

        $this->assertSame($versionAfterFirstRun, $versionAfterSecondRun);
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function assertNoDuplicateCodes(string $modelClass): void
    {
        $duplicates = $modelClass::query()
            ->selectRaw('code, count(*) as aggregate')
            ->groupBy('code')
            ->havingRaw('count(*) > 1')
            ->count();

        $this->assertSame(0, $duplicates);
    }
}
