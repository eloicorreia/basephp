<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\ApiException;
use App\Models\AdminMenuGroup;
use App\Models\AdminMenuItem;
use App\Models\AdminMenuVersion;
use App\Models\Permission;
use App\Services\Web\AdminMenuManagementService;
use App\Support\Web\WebAdminPermissions;
use Database\Seeders\AdminMenuSeeder;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class AdminMenuManagementServiceTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_create_group_logs_event_and_increments_version(): void
    {
        $actor = $this->createUser();
        $initialVersion = $this->currentVersion();

        $group = $this->service()->createGroup([
            'code' => 'management-group',
            'title' => 'Management Group',
            'order' => 50,
            'active' => true,
        ], $actor);

        $this->assertDatabaseHas('admin_menu_groups', [
            'id' => $group->id,
            'code' => 'management-group',
        ]);
        $this->assertSame($initialVersion + 1, $this->currentVersion());
        $this->assertDatabaseHas('admin_menu_event_logs', [
            'event' => 'admin_menu_group.created',
            'entity_type' => AdminMenuGroup::class,
            'entity_id' => $group->id,
        ]);
        $this->assertDatabaseHas('admin_menu_event_logs', [
            'event' => 'admin_menu.version_incremented',
            'entity_type' => 'admin_menu_versions',
            'entity_id' => 1,
        ]);
    }

    public function test_update_item_rejects_missing_route(): void
    {
        $item = $this->item();

        $this->expectException(ApiException::class);

        $this->service()->updateItem($item, [
            'route_name' => 'admin.route.missing',
        ]);
    }

    public function test_create_child_item_rejects_divergent_group_id(): void
    {
        $parent = $this->item();
        $otherGroup = AdminMenuGroup::query()->create([
            'code' => 'other-group',
            'title' => 'Other Group',
            'order' => 20,
            'active' => true,
        ]);

        $this->expectException(ApiException::class);

        $this->service()->createItem([
            'admin_menu_group_id' => $otherGroup->id,
            'parent_id' => $parent->id,
            'code' => 'child-divergent-group',
            'title' => 'Child Divergent Group',
            'route_name' => 'admin.dashboard',
            'permission_strategy' => 'any',
        ]);
    }

    public function test_create_child_item_inherits_parent_group_id(): void
    {
        $parent = $this->item();

        $child = $this->service()->createItem([
            'parent_id' => $parent->id,
            'code' => 'child-same-group',
            'title' => 'Child Same Group',
            'route_name' => 'admin.dashboard',
            'permission_strategy' => 'any',
        ]);

        $this->assertSame($parent->admin_menu_group_id, $child->admin_menu_group_id);
    }

    public function test_update_item_rejects_self_parent(): void
    {
        $item = $this->item();

        $this->expectException(ApiException::class);

        $this->service()->updateItem($item, [
            'parent_id' => $item->id,
        ]);
    }

    public function test_create_item_rejects_invalid_permission_strategy(): void
    {
        $group = AdminMenuGroup::query()->create([
            'code' => 'invalid-strategy-group',
            'title' => 'Invalid Strategy Group',
            'order' => 10,
            'active' => true,
        ]);

        $this->expectException(ApiException::class);

        $this->service()->createItem([
            'admin_menu_group_id' => $group->id,
            'code' => 'invalid-strategy-item',
            'title' => 'Invalid Strategy Item',
            'route_name' => 'admin.dashboard',
            'permission_strategy' => 'none',
        ]);
    }

    public function test_update_item_rejects_indirect_cycle(): void
    {
        $parent = $this->item();
        $child = AdminMenuItem::query()->create([
            'admin_menu_group_id' => $parent->admin_menu_group_id,
            'parent_id' => $parent->id,
            'code' => 'cycle-child',
            'title' => 'Cycle Child',
            'route_name' => 'admin.dashboard',
            'active_route_pattern' => 'admin.dashboard',
            'order' => 20,
            'active' => true,
            'opens_in_new_tab' => false,
            'permission_strategy' => 'any',
        ]);

        $this->expectException(ApiException::class);

        $this->service()->updateItem($parent, [
            'parent_id' => $child->id,
        ]);
    }

    public function test_sync_item_permissions_logs_event_and_increments_version(): void
    {
        $item = $this->item();
        $permission = Permission::query()->where('code', WebAdminPermissions::DASHBOARD_VIEW)->firstOrFail();
        $initialVersion = $this->currentVersion();

        $this->service()->syncItemPermissions($item, [$permission->id]);

        $this->assertSame($initialVersion + 1, $this->currentVersion());
        $this->assertDatabaseHas('admin_menu_item_permissions', [
            'admin_menu_item_id' => $item->id,
            'permission_id' => $permission->id,
        ]);
        $this->assertDatabaseHas('admin_menu_event_logs', [
            'event' => 'admin_menu_item.permissions_synced',
            'entity_type' => AdminMenuItem::class,
            'entity_id' => $item->id,
        ]);
    }

    public function test_admin_menu_seeder_does_not_increment_version_without_real_change(): void
    {
        $this->seed(AdminMenuSeeder::class);
        $versionAfterFirstRun = $this->currentVersion();

        $this->seed(AdminMenuSeeder::class);

        $this->assertSame($versionAfterFirstRun, $this->currentVersion());
    }

    private function service(): AdminMenuManagementService
    {
        return app(AdminMenuManagementService::class);
    }

    private function currentVersion(): int
    {
        return (int) AdminMenuVersion::query()->whereKey(1)->value('version');
    }

    private function item(): AdminMenuItem
    {
        $group = AdminMenuGroup::query()->create([
            'code' => 'management-items',
            'title' => 'Management Items',
            'order' => 10,
            'active' => true,
        ]);

        return AdminMenuItem::query()->create([
            'admin_menu_group_id' => $group->id,
            'parent_id' => null,
            'code' => 'management-item-'.uniqid(),
            'title' => 'Management Item',
            'route_name' => 'admin.dashboard',
            'active_route_pattern' => 'admin.dashboard',
            'icon' => 'ri-test-line',
            'order' => 10,
            'active' => true,
            'opens_in_new_tab' => false,
            'permission_strategy' => 'any',
        ]);
    }
}
