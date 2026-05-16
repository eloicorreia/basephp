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
        $dashboardGroup = AdminMenuGroup::query()->updateOrCreate(
            ['code' => 'painel'],
            [
                'title' => 'Painel',
                'translation_key' => null,
                'icon' => null,
                'order' => 10,
                'active' => true,
            ]
        );

        $operationGroup = AdminMenuGroup::query()->updateOrCreate(
            ['code' => 'operacao'],
            [
                'title' => 'Operação',
                'translation_key' => null,
                'icon' => null,
                'order' => 20,
                'active' => true,
            ]
        );

        $dashboardItem = AdminMenuItem::query()->updateOrCreate(
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
            ]
        );

        $apiRequestLogsItem = AdminMenuItem::query()->updateOrCreate(
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
            ]
        );

        $this->syncPermission($dashboardItem, WebAdminPermissions::DASHBOARD_VIEW);
        $this->syncPermission($apiRequestLogsItem, WebAdminPermissions::API_REQUEST_LOGS_VIEW);

        app(AdminMenuVersionService::class)->increment();
    }

    private function syncPermission(AdminMenuItem $item, string $permissionCode): void
    {
        $permission = Permission::query()
            ->where('code', $permissionCode)
            ->first();

        if (! $permission instanceof Permission) {
            return;
        }

        $item->permissions()->syncWithoutDetaching([$permission->id]);
    }
}
