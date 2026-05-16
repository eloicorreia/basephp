<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdminMenuPermissionStrategy;
use App\Models\AdminMenuGroup;
use App\Models\AdminMenuItem;
use App\Models\Permission;
use App\Services\Web\AdminMenuVersionService;
use App\Support\Web\WebAdminPermissions;
use Illuminate\Database\Seeder;

class AdminMenuSeeder extends Seeder
{
    public function run(): void
    {
        $changed = false;

        $dashboardGroup = $this->updateOrCreateGroup(
            ['code' => 'painel'],
            [
                'title' => 'Painel',
                'translation_key' => null,
                'icon' => null,
                'order' => 10,
                'active' => true,
            ],
            $changed
        );

        $operationGroup = $this->updateOrCreateGroup(
            ['code' => 'operacao'],
            [
                'title' => 'Operação',
                'translation_key' => null,
                'icon' => null,
                'order' => 20,
                'active' => true,
            ],
            $changed
        );

        $dashboardItem = $this->updateOrCreateItem(
            ['code' => 'dashboard'],
            [
                'admin_menu_group_id' => $dashboardGroup->id,
                'parent_id' => null,
                'title' => 'Dashboard',
                'translation_key' => null,
                'route_name' => 'admin.dashboard',
                'active_route_pattern' => 'admin.dashboard',
                'icon' => 'ri-dashboard-2-line',
                'order' => 10,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $apiRequestLogsItem = $this->updateOrCreateItem(
            ['code' => 'api-request-logs'],
            [
                'admin_menu_group_id' => $operationGroup->id,
                'parent_id' => null,
                'title' => 'Logs da API',
                'translation_key' => null,
                'route_name' => 'admin.logs.api-requests.index',
                'active_route_pattern' => 'admin.logs.*',
                'icon' => 'ri-file-list-3-line',
                'order' => 10,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $this->syncPermission($dashboardItem, WebAdminPermissions::DASHBOARD_VIEW, $changed);
        $this->syncPermission($apiRequestLogsItem, WebAdminPermissions::API_REQUEST_LOGS_VIEW, $changed);

        if ($changed) {
            app(AdminMenuVersionService::class)->increment();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    private function updateOrCreateGroup(array $attributes, array $values, bool &$changed): AdminMenuGroup
    {
        $group = AdminMenuGroup::query()->firstOrNew($attributes);
        $group->fill($values);
        $groupChanged = ! $group->exists || $group->isDirty();
        $group->save();

        $changed = $changed || $groupChanged;

        return $group;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    private function updateOrCreateItem(array $attributes, array $values, bool &$changed): AdminMenuItem
    {
        $item = AdminMenuItem::query()->firstOrNew($attributes);
        $item->fill($values);
        $itemChanged = ! $item->exists || $item->isDirty();
        $item->save();

        $changed = $changed || $itemChanged;

        return $item;
    }

    private function syncPermission(AdminMenuItem $item, string $permissionCode, bool &$changed): void
    {
        $permission = Permission::query()
            ->where('code', $permissionCode)
            ->first();

        if (! $permission instanceof Permission) {
            return;
        }

        if (! $item->permissions()->whereKey($permission->id)->exists()) {
            $item->permissions()->attach($permission->id);
            $changed = true;
        }
    }
}
