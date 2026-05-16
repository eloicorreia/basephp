<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AdminMenuPermissionStrategy;
use App\Models\AdminMenuEventLog;
use App\Models\AdminMenuGroup;
use App\Models\AdminMenuItem;
use App\Models\Permission;
use App\Models\User;
use App\Services\Web\AdminMenuBuilderService;
use App\Services\Web\AdminMenuVersionService;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminMenuBuilderServiceTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetMenu();
    }

    public function test_user_without_permission_does_not_see_item(): void
    {
        $permission = $this->permission('custom.menu.hidden');
        $this->menuItem('hidden-item', 'Hidden Item', [$permission->id]);
        $user = $this->userWithPermissions([]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertMenuDoesNotContain($menu, 'hidden-item');
    }

    public function test_user_with_permission_sees_item(): void
    {
        $permission = $this->permission('custom.menu.visible');
        $this->menuItem('visible-item', 'Visible Item', [$permission->id]);
        $user = $this->userWithPermissions([$permission]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertMenuContains($menu, 'visible-item');
    }

    public function test_admin_full_sees_all_active_items(): void
    {
        $permission = $this->permission('custom.menu.admin-full');
        $this->menuItem('admin-full-item', 'Admin Full Item', [$permission->id]);
        $user = $this->userWithPermissions([$this->permission(PermissionRegistry::ADMIN_FULL)]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertMenuContains($menu, 'admin-full-item');
    }

    public function test_inactive_item_does_not_appear(): void
    {
        $permission = $this->permission('custom.menu.inactive');
        $this->menuItem('inactive-item', 'Inactive Item', [$permission->id], active: false);
        $user = $this->userWithPermissions([$permission]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertMenuDoesNotContain($menu, 'inactive-item');
    }

    public function test_group_without_visible_items_does_not_appear(): void
    {
        $permission = $this->permission('custom.menu.group-hidden');
        $this->menuItem('group-hidden-item', 'Group Hidden Item', [$permission->id]);
        $user = $this->userWithPermissions([]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertSame([], $menu);
    }

    public function test_parent_without_permission_appears_when_child_is_visible(): void
    {
        $permission = $this->permission('custom.menu.child-visible');
        $parent = $this->menuItem('parent-visible', 'Parent Visible', [], routeName: null);
        $this->menuItem('child-visible', 'Child Visible', [$permission->id], parent: $parent);
        $user = $this->userWithPermissions([$permission]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertMenuContains($menu, 'parent-visible');
        $this->assertMenuContains($menu, 'child-visible');
    }

    public function test_parent_without_permission_and_without_visible_child_does_not_appear(): void
    {
        $permission = $this->permission('custom.menu.child-hidden');
        $parent = $this->menuItem('parent-hidden', 'Parent Hidden', [], routeName: null);
        $this->menuItem('child-hidden', 'Child Hidden', [$permission->id], parent: $parent);
        $user = $this->userWithPermissions([]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertMenuDoesNotContain($menu, 'parent-hidden');
    }

    public function test_permission_strategy_any_allows_one_permission(): void
    {
        $first = $this->permission('custom.menu.any.first');
        $second = $this->permission('custom.menu.any.second');
        $this->menuItem('any-item', 'Any Item', [$first->id, $second->id]);
        $user = $this->userWithPermissions([$first]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertMenuContains($menu, 'any-item');
    }

    public function test_permission_strategy_all_requires_all_permissions(): void
    {
        $first = $this->permission('custom.menu.all.first');
        $second = $this->permission('custom.menu.all.second');
        $this->menuItem(
            code: 'all-item',
            title: 'All Item',
            permissionIds: [$first->id, $second->id],
            strategy: AdminMenuPermissionStrategy::ALL,
        );

        $partialUser = $this->userWithPermissions([$first]);
        $fullUser = $this->userWithPermissions([$first, $second]);

        $this->assertMenuDoesNotContain($this->builder()->buildForUser($partialUser), 'all-item');
        $this->assertMenuContains($this->builder()->buildForUser($fullUser), 'all-item');
    }

    public function test_missing_route_does_not_break_and_logs_event(): void
    {
        $permission = $this->permission('custom.menu.invalid-route');
        $this->menuItem('invalid-route-item', 'Invalid Route Item', [$permission->id], routeName: 'admin.missing.route');
        $user = $this->userWithPermissions([$permission]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertMenuDoesNotContain($menu, 'invalid-route-item');
        $this->assertDatabaseHas('admin_menu_event_logs', [
            'event' => 'admin_menu.route_not_found',
            'entity_type' => AdminMenuItem::class,
        ]);
    }

    public function test_cycle_does_not_break_and_logs_event(): void
    {
        $permission = $this->permission('custom.menu.cycle');
        $first = $this->menuItem('cycle-first', 'Cycle First', [$permission->id], routeName: null);
        $second = $this->menuItem('cycle-second', 'Cycle Second', [$permission->id], parent: $first, routeName: null);
        $first->forceFill(['parent_id' => $second->id])->save();
        $user = $this->userWithPermissions([$permission]);

        $menu = $this->builder()->buildForUser($user);

        $this->assertIsArray($menu);
        $this->assertDatabaseHas('admin_menu_event_logs', [
            'event' => 'admin_menu.cycle_detected',
        ]);
    }

    public function test_cache_is_used_until_menu_version_changes(): void
    {
        $permission = $this->permission('custom.menu.cache');
        $item = $this->menuItem('cache-item', 'Cache Item', [$permission->id]);
        $user = $this->userWithPermissions([$permission]);

        $firstMenu = $this->builder()->buildForUser($user);
        $item->forceFill(['title' => 'Cache Item Changed'])->save();
        $secondMenu = $this->builder()->buildForUser($user);

        app(AdminMenuVersionService::class)->increment($user->id);
        $thirdMenu = $this->builder()->buildForUser($user);

        $this->assertSame($this->findMenuItem($firstMenu, 'cache-item'), $this->findMenuItem($secondMenu, 'cache-item'));
        $this->assertSame('Cache Item Changed', $this->findMenuItem($thirdMenu, 'cache-item')['title'] ?? null);
    }

    private function builder(): AdminMenuBuilderService
    {
        return app(AdminMenuBuilderService::class);
    }

    private function resetMenu(): void
    {
        Cache::forget('unused');
        AdminMenuEventLog::query()->delete();
        AdminMenuItem::query()->delete();
        AdminMenuGroup::query()->delete();
    }

    private function permission(string $code): Permission
    {
        return Permission::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => 'Permission '.$code,
                'description' => 'Permission for menu tests.',
                'group' => 'Menu Tests',
                'context' => 'web',
                'is_system' => false,
                'is_sensitive' => false,
                'active' => true,
            ]
        );
    }

    /**
     * @param  list<Permission>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = $this->createRole('menu-role-'.str_replace('-', '', (string) Str::uuid()), 'Menu Role');
        $syncPayload = [];

        foreach ($permissions as $permission) {
            $syncPayload[$permission->id] = ['assigned_at' => now()];
        }

        $role->permissions()->sync($syncPayload);

        return $this->createUser(role: $role);
    }

    /**
     * @param  list<int>  $permissionIds
     */
    private function menuItem(
        string $code,
        string $title,
        array $permissionIds,
        ?AdminMenuItem $parent = null,
        ?string $routeName = 'admin.dashboard',
        bool $active = true,
        AdminMenuPermissionStrategy $strategy = AdminMenuPermissionStrategy::ANY,
    ): AdminMenuItem {
        $group = AdminMenuGroup::query()->firstOrCreate(
            ['code' => 'menu-tests'],
            [
                'title' => 'Menu Tests',
                'order' => 10,
                'active' => true,
            ]
        );

        $item = AdminMenuItem::query()->create([
            'admin_menu_group_id' => $parent instanceof AdminMenuItem ? null : $group->id,
            'parent_id' => $parent?->id,
            'code' => $code,
            'title' => $title,
            'route_name' => $routeName,
            'active_route_pattern' => $routeName,
            'icon' => 'ri-test-line',
            'order' => 10,
            'active' => $active,
            'opens_in_new_tab' => false,
            'permission_strategy' => $strategy->value,
        ]);

        $item->permissions()->sync($permissionIds);

        return $item;
    }

    /**
     * @param  list<array<string, mixed>>  $menu
     */
    private function assertMenuContains(array $menu, string $code): void
    {
        $this->assertNotNull($this->findMenuItem($menu, $code), 'Menu item '.$code.' was not found.');
    }

    /**
     * @param  list<array<string, mixed>>  $menu
     */
    private function assertMenuDoesNotContain(array $menu, string $code): void
    {
        $this->assertNull($this->findMenuItem($menu, $code), 'Menu item '.$code.' should not be visible.');
    }

    /**
     * @param  list<array<string, mixed>>  $menu
     * @return array<string, mixed>|null
     */
    private function findMenuItem(array $menu, string $code): ?array
    {
        foreach ($menu as $group) {
            $found = $this->findMenuItemInItems($group['items'] ?? [], $code);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>|null
     */
    private function findMenuItemInItems(array $items, string $code): ?array
    {
        foreach ($items as $item) {
            if (($item['code'] ?? null) === $code) {
                return $item;
            }

            $found = $this->findMenuItemInItems($item['children'] ?? [], $code);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }
}
