<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdminMenuPermissionStrategy;
use App\Models\AdminMenuGroup;
use App\Models\AdminMenuItem;
use App\Models\Permission;
use App\Services\Web\AdminMenuVersionService;
use App\Support\Web\WebAdminPermissions;
use Database\Seeders\Concerns\WritesSeederOutput;
use Illuminate\Database\Seeder;

class AdminMenuSeeder extends Seeder
{
    use WritesSeederOutput;

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

        $securityGroup = $this->updateOrCreateGroup(
            ['code' => 'seguranca'],
            [
                'title' => 'Segurança',
                'translation_key' => null,
                'icon' => null,
                'order' => 30,
                'active' => true,
            ],
            $changed
        );

        $systemSettingsGroup = $this->updateOrCreateGroup(
            ['code' => 'configuracoes-sistema'],
            [
                'title' => 'Configurações',
                'translation_key' => null,
                'icon' => null,
                'order' => 40,
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

        $securityItem = $this->updateOrCreateItem(
            ['code' => 'security'],
            [
                'admin_menu_group_id' => $securityGroup->id,
                'parent_id' => null,
                'title' => 'Segurança',
                'translation_key' => null,
                'route_name' => 'admin.security.index',
                'active_route_pattern' => 'admin.security.*',
                'icon' => 'ri-shield-keyhole-line',
                'order' => 10,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $securityUsersItem = $this->updateOrCreateItem(
            ['code' => 'security-users'],
            [
                'admin_menu_group_id' => $securityGroup->id,
                'parent_id' => $securityItem->id,
                'title' => 'Usuários',
                'translation_key' => null,
                'route_name' => 'admin.security.users.index',
                'active_route_pattern' => 'admin.security.users.*',
                'icon' => null,
                'order' => 10,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $securityRolesItem = $this->updateOrCreateItem(
            ['code' => 'security-roles'],
            [
                'admin_menu_group_id' => $securityGroup->id,
                'parent_id' => $securityItem->id,
                'title' => 'Roles',
                'translation_key' => null,
                'route_name' => 'admin.security.roles.index',
                'active_route_pattern' => 'admin.security.roles.*',
                'icon' => null,
                'order' => 20,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $securityPermissionsItem = $this->updateOrCreateItem(
            ['code' => 'security-permissions'],
            [
                'admin_menu_group_id' => $securityGroup->id,
                'parent_id' => $securityItem->id,
                'title' => 'Permissões',
                'translation_key' => null,
                'route_name' => 'admin.security.permissions.index',
                'active_route_pattern' => 'admin.security.permissions.*',
                'icon' => null,
                'order' => 30,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $systemSettingsItem = $this->updateOrCreateItem(
            ['code' => 'system-settings'],
            [
                'admin_menu_group_id' => $systemSettingsGroup->id,
                'parent_id' => null,
                'title' => 'Sistema',
                'translation_key' => null,
                'route_name' => 'admin.system-settings.index',
                'active_route_pattern' => 'admin.system-settings.*',
                'icon' => 'ri-settings-3-line',
                'order' => 10,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $systemSettingsGeneralItem = $this->updateOrCreateItem(
            ['code' => 'system-settings-general'],
            [
                'admin_menu_group_id' => $systemSettingsGroup->id,
                'parent_id' => $systemSettingsItem->id,
                'title' => 'Geral',
                'translation_key' => null,
                'route_name' => 'admin.system-settings.general.edit',
                'active_route_pattern' => 'admin.system-settings.general.*',
                'icon' => null,
                'order' => 10,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $systemSettingsSecurityItem = $this->updateOrCreateItem(
            ['code' => 'system-settings-security'],
            [
                'admin_menu_group_id' => $systemSettingsGroup->id,
                'parent_id' => $systemSettingsItem->id,
                'title' => 'Segurança',
                'translation_key' => null,
                'route_name' => 'admin.system-settings.security.edit',
                'active_route_pattern' => 'admin.system-settings.security.*',
                'icon' => null,
                'order' => 20,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $systemSettingsPasswordsItem = $this->updateOrCreateItem(
            ['code' => 'system-settings-passwords'],
            [
                'admin_menu_group_id' => $systemSettingsGroup->id,
                'parent_id' => $systemSettingsItem->id,
                'title' => 'Senhas',
                'translation_key' => null,
                'route_name' => 'admin.system-settings.password-policy.edit',
                'active_route_pattern' => 'admin.system-settings.password-policy.*',
                'icon' => null,
                'order' => 30,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $systemSettingsMailItem = $this->updateOrCreateItem(
            ['code' => 'system-settings-mail'],
            [
                'admin_menu_group_id' => $systemSettingsGroup->id,
                'parent_id' => $systemSettingsItem->id,
                'title' => 'E-mail',
                'translation_key' => null,
                'route_name' => 'admin.system-settings.mail.edit',
                'active_route_pattern' => 'admin.system-settings.mail.*',
                'icon' => null,
                'order' => 40,
                'active' => true,
                'opens_in_new_tab' => false,
                'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
            ],
            $changed
        );

        $systemSettingsApiItem = $this->updateOrCreateItem(['code' => 'system-settings-api'], [
            'admin_menu_group_id' => $systemSettingsGroup->id,
            'parent_id' => $systemSettingsItem->id,
            'title' => 'API',
            'translation_key' => null,
            'route_name' => 'admin.system-settings.api.edit',
            'active_route_pattern' => 'admin.system-settings.api.*',
            'icon' => null,
            'order' => 50,
            'active' => true,
            'opens_in_new_tab' => false,
            'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
        ], $changed);

        $systemSettingsQueueItem = $this->updateOrCreateItem(['code' => 'system-settings-queues'], [
            'admin_menu_group_id' => $systemSettingsGroup->id,
            'parent_id' => $systemSettingsItem->id,
            'title' => 'Filas',
            'translation_key' => null,
            'route_name' => 'admin.system-settings.queues.edit',
            'active_route_pattern' => 'admin.system-settings.queues.*',
            'icon' => null,
            'order' => 60,
            'active' => true,
            'opens_in_new_tab' => false,
            'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
        ], $changed);

        $systemSettingsAuditItem = $this->updateOrCreateItem(['code' => 'system-settings-audit'], [
            'admin_menu_group_id' => $systemSettingsGroup->id,
            'parent_id' => $systemSettingsItem->id,
            'title' => 'Logs e Auditoria',
            'translation_key' => null,
            'route_name' => 'admin.system-settings.audit.edit',
            'active_route_pattern' => 'admin.system-settings.audit.*',
            'icon' => null,
            'order' => 70,
            'active' => true,
            'opens_in_new_tab' => false,
            'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
        ], $changed);

        $systemSettingsIntegrationItem = $this->updateOrCreateItem(['code' => 'system-settings-integrations'], [
            'admin_menu_group_id' => $systemSettingsGroup->id,
            'parent_id' => $systemSettingsItem->id,
            'title' => 'Integrações',
            'translation_key' => null,
            'route_name' => 'admin.system-settings.integrations.edit',
            'active_route_pattern' => 'admin.system-settings.integrations.*',
            'icon' => null,
            'order' => 80,
            'active' => true,
            'opens_in_new_tab' => false,
            'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
        ], $changed);

        $systemSettingsWebhookItem = $this->updateOrCreateItem(['code' => 'system-settings-webhooks'], [
            'admin_menu_group_id' => $systemSettingsGroup->id,
            'parent_id' => $systemSettingsItem->id,
            'title' => 'Webhooks',
            'translation_key' => null,
            'route_name' => 'admin.system-settings.webhooks.edit',
            'active_route_pattern' => 'admin.system-settings.webhooks.*',
            'icon' => null,
            'order' => 90,
            'active' => true,
            'opens_in_new_tab' => false,
            'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
        ], $changed);

        $systemSettingsNotificationItem = $this->updateOrCreateItem(['code' => 'system-settings-notifications'], [
            'admin_menu_group_id' => $systemSettingsGroup->id,
            'parent_id' => $systemSettingsItem->id,
            'title' => 'Notificações',
            'translation_key' => null,
            'route_name' => 'admin.system-settings.notifications.edit',
            'active_route_pattern' => 'admin.system-settings.notifications.*',
            'icon' => null,
            'order' => 100,
            'active' => true,
            'opens_in_new_tab' => false,
            'permission_strategy' => AdminMenuPermissionStrategy::ANY->value,
        ], $changed);

        $this->syncPermission($dashboardItem, WebAdminPermissions::DASHBOARD_VIEW, $changed);
        $this->syncPermission($apiRequestLogsItem, WebAdminPermissions::API_REQUEST_LOGS_VIEW, $changed);
        $this->syncPermission($securityItem, WebAdminPermissions::SECURITY_VIEW, $changed);
        $this->syncPermission($securityUsersItem, WebAdminPermissions::SECURITY_USERS_MANAGE, $changed);
        $this->syncPermission($securityRolesItem, WebAdminPermissions::SECURITY_ROLES_MANAGE, $changed);
        $this->syncPermission($securityPermissionsItem, WebAdminPermissions::SECURITY_PERMISSIONS_MANAGE, $changed);
        $this->syncPermission($systemSettingsItem, WebAdminPermissions::SYSTEM_SETTINGS_VIEW, $changed);
        $this->syncPermission($systemSettingsGeneralItem, WebAdminPermissions::SYSTEM_SETTINGS_GENERAL_MANAGE, $changed);
        $this->syncPermission($systemSettingsSecurityItem, WebAdminPermissions::SYSTEM_SETTINGS_SECURITY_MANAGE, $changed);
        $this->syncPermission($systemSettingsPasswordsItem, WebAdminPermissions::SYSTEM_SETTINGS_PASSWORD_POLICY_MANAGE, $changed);
        $this->syncPermission($systemSettingsMailItem, WebAdminPermissions::SYSTEM_SETTINGS_MAIL_MANAGE, $changed);
        $this->syncPermission($systemSettingsApiItem, WebAdminPermissions::SYSTEM_SETTINGS_API_MANAGE, $changed);
        $this->syncPermission($systemSettingsQueueItem, WebAdminPermissions::SYSTEM_SETTINGS_QUEUE_MANAGE, $changed);
        $this->syncPermission($systemSettingsAuditItem, WebAdminPermissions::SYSTEM_SETTINGS_AUDIT_MANAGE, $changed);
        $this->syncPermission($systemSettingsIntegrationItem, WebAdminPermissions::SYSTEM_SETTINGS_INTEGRATION_MANAGE, $changed);
        $this->syncPermission($systemSettingsWebhookItem, WebAdminPermissions::SYSTEM_SETTINGS_WEBHOOK_MANAGE, $changed);
        $this->syncPermission($systemSettingsNotificationItem, WebAdminPermissions::SYSTEM_SETTINGS_NOTIFICATION_MANAGE, $changed);

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
            $this->seederWarn(sprintf(
                'Permissão obrigatória do menu não encontrada: %s.',
                $permissionCode
            ));

            return;
        }

        if (! $item->permissions()->whereKey($permission->id)->exists()) {
            $item->permissions()->attach($permission->id);
            $changed = true;
        }
    }
}
