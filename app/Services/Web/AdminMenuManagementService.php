<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Enums\AdminMenuPermissionStrategy;
use App\Exceptions\ApiException;
use App\Models\AdminMenuGroup;
use App\Models\AdminMenuItem;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

final class AdminMenuManagementService
{
    public function __construct(
        private readonly AdminMenuVersionService $versionService,
        private readonly AdminMenuEventLogger $eventLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createGroup(array $data, ?User $actor = null): AdminMenuGroup
    {
        return DB::transaction(function () use ($data, $actor): AdminMenuGroup {
            $group = AdminMenuGroup::query()->create($data);
            $version = $this->incrementVersion($actor);

            $this->eventLogger->log(
                event: 'admin_menu_group.created',
                entityType: AdminMenuGroup::class,
                entityId: $group->id,
                newValues: $this->groupSnapshot($group),
                context: ['version' => $version],
            );

            return $group;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGroup(AdminMenuGroup $group, array $data, ?User $actor = null): AdminMenuGroup
    {
        return DB::transaction(function () use ($group, $data, $actor): AdminMenuGroup {
            $group = AdminMenuGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            $before = $this->groupSnapshot($group);
            $group->fill($data)->save();
            $after = $this->groupSnapshot($group);
            $version = $this->incrementVersion($actor);

            $this->eventLogger->log(
                event: 'admin_menu_group.updated',
                entityType: AdminMenuGroup::class,
                entityId: $group->id,
                oldValues: $before,
                newValues: $after,
                context: ['version' => $version],
            );

            return $group->refresh();
        });
    }

    public function activateGroup(AdminMenuGroup $group, ?User $actor = null): AdminMenuGroup
    {
        return $this->setGroupActive($group, true, $actor);
    }

    public function deactivateGroup(AdminMenuGroup $group, ?User $actor = null): AdminMenuGroup
    {
        return $this->setGroupActive($group, false, $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data, ?User $actor = null): AdminMenuItem
    {
        return DB::transaction(function () use ($data, $actor): AdminMenuItem {
            $normalizedData = $this->normalizeItemData($data);
            $item = AdminMenuItem::query()->create($normalizedData);
            $version = $this->incrementVersion($actor);

            $this->eventLogger->log(
                event: 'admin_menu_item.created',
                entityType: AdminMenuItem::class,
                entityId: $item->id,
                newValues: $this->itemSnapshot($item),
                context: ['version' => $version],
            );

            return $item;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(AdminMenuItem $item, array $data, ?User $actor = null): AdminMenuItem
    {
        return DB::transaction(function () use ($item, $data, $actor): AdminMenuItem {
            $item = AdminMenuItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $before = $this->itemSnapshot($item);
            $normalizedData = $this->normalizeItemData($data, $item);

            $item->fill($normalizedData)->save();
            $after = $this->itemSnapshot($item);
            $version = $this->incrementVersion($actor);

            $this->eventLogger->log(
                event: 'admin_menu_item.updated',
                entityType: AdminMenuItem::class,
                entityId: $item->id,
                oldValues: $before,
                newValues: $after,
                context: ['version' => $version],
            );

            return $item->refresh();
        });
    }

    public function activateItem(AdminMenuItem $item, ?User $actor = null): AdminMenuItem
    {
        return $this->setItemActive($item, true, $actor);
    }

    public function deactivateItem(AdminMenuItem $item, ?User $actor = null): AdminMenuItem
    {
        return $this->setItemActive($item, false, $actor);
    }

    /**
     * @param  list<int>  $permissionIds
     */
    public function syncItemPermissions(AdminMenuItem $item, array $permissionIds, ?User $actor = null): AdminMenuItem
    {
        return DB::transaction(function () use ($item, $permissionIds, $actor): AdminMenuItem {
            $item = AdminMenuItem::query()
                ->with('permissions')
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $this->permissionSnapshot($item);
            $validPermissionIds = Permission::query()
                ->whereIn('id', $permissionIds)
                ->lockForUpdate()
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            $item->permissions()->sync($validPermissionIds);
            $item->load('permissions');
            $after = $this->permissionSnapshot($item);
            $version = $this->incrementVersion($actor);

            $this->eventLogger->log(
                event: 'admin_menu_item.permissions_synced',
                entityType: AdminMenuItem::class,
                entityId: $item->id,
                oldValues: ['permissions' => $before],
                newValues: ['permissions' => $after],
                context: ['version' => $version],
            );

            return $item;
        });
    }

    private function setGroupActive(AdminMenuGroup $group, bool $active, ?User $actor): AdminMenuGroup
    {
        return DB::transaction(function () use ($group, $active, $actor): AdminMenuGroup {
            $group = AdminMenuGroup::query()->whereKey($group->id)->lockForUpdate()->firstOrFail();
            $before = $this->groupSnapshot($group);
            $group->forceFill(['active' => $active])->save();
            $after = $this->groupSnapshot($group);
            $version = $this->incrementVersion($actor);

            $this->eventLogger->log(
                event: $active ? 'admin_menu_group.activated' : 'admin_menu_group.deactivated',
                entityType: AdminMenuGroup::class,
                entityId: $group->id,
                oldValues: $before,
                newValues: $after,
                context: ['version' => $version],
            );

            return $group->refresh();
        });
    }

    private function setItemActive(AdminMenuItem $item, bool $active, ?User $actor): AdminMenuItem
    {
        return DB::transaction(function () use ($item, $active, $actor): AdminMenuItem {
            $item = AdminMenuItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $before = $this->itemSnapshot($item);
            $item->forceFill(['active' => $active])->save();
            $after = $this->itemSnapshot($item);
            $version = $this->incrementVersion($actor);

            $this->eventLogger->log(
                event: $active ? 'admin_menu_item.activated' : 'admin_menu_item.deactivated',
                entityType: AdminMenuItem::class,
                entityId: $item->id,
                oldValues: $before,
                newValues: $after,
                context: ['version' => $version],
            );

            return $item->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeItemData(array $data, ?AdminMenuItem $currentItem = null): array
    {
        $routeName = $data['route_name'] ?? $currentItem?->route_name;

        if (is_string($routeName) && $routeName !== '' && ! Route::has($routeName)) {
            throw new ApiException('Route name informado para o menu administrativo não existe.', 422);
        }

        $strategy = $data['permission_strategy'] ?? $currentItem?->permission_strategy->value ?? AdminMenuPermissionStrategy::ANY->value;

        if ($strategy instanceof AdminMenuPermissionStrategy) {
            $strategy = $strategy->value;
        }

        if (! is_string($strategy) || AdminMenuPermissionStrategy::tryFrom($strategy) === null) {
            throw new ApiException('Estratégia de permissão do menu deve ser any ou all.', 422);
        }

        $data['permission_strategy'] = $strategy;
        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $currentItem?->parent_id;

        if ($parentId !== null) {
            $parent = AdminMenuItem::query()->whereKey((int) $parentId)->lockForUpdate()->firstOrFail();

            if ($currentItem instanceof AdminMenuItem && (int) $parent->id === (int) $currentItem->id) {
                throw new ApiException('Item de menu não pode ser pai dele mesmo.', 422);
            }

            if ($currentItem instanceof AdminMenuItem && $this->wouldCreateCycle($currentItem, $parent)) {
                throw new ApiException('Hierarquia de menu não pode gerar ciclo.', 422);
            }

            $incomingGroupId = $data['admin_menu_group_id'] ?? $currentItem?->admin_menu_group_id;

            if ($incomingGroupId !== null && (int) $incomingGroupId !== (int) $parent->admin_menu_group_id) {
                throw new ApiException('Item filho deve usar o mesmo grupo do item pai.', 422);
            }

            $data['admin_menu_group_id'] = $parent->admin_menu_group_id;
        }

        return $data;
    }

    private function wouldCreateCycle(AdminMenuItem $item, AdminMenuItem $newParent): bool
    {
        $current = $newParent;

        while ($current->parent_id !== null) {
            if ((int) $current->parent_id === (int) $item->id) {
                return true;
            }

            $current = AdminMenuItem::query()
                ->whereKey((int) $current->parent_id)
                ->lockForUpdate()
                ->first();

            if (! $current instanceof AdminMenuItem) {
                return false;
            }
        }

        return false;
    }

    private function incrementVersion(?User $actor): int
    {
        $version = $this->versionService->increment($actor?->id);

        $this->eventLogger->log(
            event: 'admin_menu.version_incremented',
            entityType: 'admin_menu_versions',
            entityId: 1,
            context: ['version' => $version],
        );

        return $version;
    }

    /**
     * @return array<string, mixed>
     */
    private function groupSnapshot(AdminMenuGroup $group): array
    {
        return [
            'id' => $group->id,
            'code' => $group->code,
            'title' => $group->title,
            'translation_key' => $group->translation_key,
            'icon' => $group->icon,
            'order' => $group->order,
            'active' => $group->active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemSnapshot(AdminMenuItem $item): array
    {
        return [
            'id' => $item->id,
            'admin_menu_group_id' => $item->admin_menu_group_id,
            'parent_id' => $item->parent_id,
            'code' => $item->code,
            'title' => $item->title,
            'translation_key' => $item->translation_key,
            'route_name' => $item->route_name,
            'active_route_pattern' => $item->active_route_pattern,
            'icon' => $item->icon,
            'order' => $item->order,
            'active' => $item->active,
            'opens_in_new_tab' => $item->opens_in_new_tab,
            'permission_strategy' => $item->permission_strategy->value,
        ];
    }

    /**
     * @return list<array{id: int, code: string}>
     */
    private function permissionSnapshot(AdminMenuItem $item): array
    {
        $permissions = [];

        foreach ($item->permissions->sortBy('code') as $permission) {
            if ($permission instanceof Permission) {
                $permissions[] = [
                    'id' => $permission->id,
                    'code' => $permission->code,
                ];
            }
        }

        return $permissions;
    }
}
