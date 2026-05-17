<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Admin;

use App\Enums\RoleCode;
use App\Models\AdminMenuGroup;
use App\Models\AdminMenuItem;
use App\Models\Permission;
use App\Support\Web\WebAdminPermissions;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminMenuRenderingTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_sidebar_renders_only_permitted_items(): void
    {
        $role = $this->createRole('menu-render-user', 'Menu Render User');
        $access = Permission::query()->where('code', WebAdminPermissions::ACCESS)->firstOrFail();
        $dashboard = Permission::query()->where('code', WebAdminPermissions::DASHBOARD_VIEW)->firstOrFail();
        $role->permissions()->sync([
            $access->id => ['assigned_at' => now()],
            $dashboard->id => ['assigned_at' => now()],
        ]);
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web');

        $html = view('admin.partials.sidebar', [
            'templateAssets' => '/vendor/templateweb/master/assets',
        ])
            ->render();

        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringNotContainsString('api-request-logs', $html);
        $this->assertStringNotContainsString('<span>Menu</span>', $html);
    }

    public function test_admin_full_sidebar_renders_seeded_items(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web');

        $html = view('admin.partials.sidebar', [
            'templateAssets' => '/vendor/templateweb/master/assets',
        ])
            ->render();

        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('Logs da API', $html);
        $this->assertStringContainsString('admin/logs/api-requests', $html);
        $this->assertStringContainsString('Configurações do Sistema', $html);
        $this->assertStringContainsString('system-settings/password-policy', $html);
        $this->assertStringContainsString('system-settings/mail', $html);
        $this->assertStringNotContainsString('Nenhum menu disponível', $html);
    }

    public function test_sidebar_renders_turbo_permanent_navigation_contract(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web');

        $html = view('admin.partials.sidebar', [
            'templateAssets' => '/vendor/templateweb/master/assets',
        ])
            ->render();

        $this->assertStringContainsString('id="admin-sidebar"', $html);
        $this->assertStringContainsString('data-turbo-permanent', $html);
        $this->assertStringContainsString('data-admin-menu-link', $html);
        $this->assertStringContainsString('data-admin-menu-parent', $html);
    }

    public function test_admin_layout_renders_stable_navigation_contract(): void
    {
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee("sessionStorage.removeItem('defaultAttribute')", false)
            ->assertSee('data-layout-width="fluid"', false)
            ->assertSee('data-layout-position="fixed"', false)
            ->assertSee('data-layout-style="default"', false)
            ->assertSee('data-layout-direction="ltr"', false)
            ->assertSee('data-bs-theme="light"', false)
            ->assertSee('data-theme-colors="default"', false)
            ->assertSee('js/turbo.es2017-esm.js', false)
            ->assertSee('js/admin-navigation.js', false)
            ->assertSee('fonts/hkgrotesk-regular.woff2', false)
            ->assertSee('data-turbo="false"', false);
    }

    public function test_sidebar_renders_fallback_when_no_menu_is_visible(): void
    {
        AdminMenuItem::query()->delete();
        AdminMenuGroup::query()->delete();
        $role = $this->createRole(RoleCode::ADMIN->value, 'Administrador');
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web');

        $html = view('admin.partials.sidebar', [
            'templateAssets' => '/vendor/templateweb/master/assets',
        ])
            ->render();

        $this->assertStringContainsString('<span>Menu</span>', $html);
        $this->assertStringContainsString('Nenhum menu disponível', $html);
    }

    public function test_direct_route_access_remains_protected_by_permission_middleware(): void
    {
        $role = $this->createRole('menu-direct-denied', 'Menu Direct Denied');
        $access = Permission::query()->where('code', WebAdminPermissions::ACCESS)->firstOrFail();
        $role->permissions()->sync([$access->id => ['assigned_at' => now()]]);
        $user = $this->createUser(role: $role);

        $this->actingAs($user, 'web')
            ->get(route('admin.logs.api-requests.index'))
            ->assertForbidden();
    }
}
