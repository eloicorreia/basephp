<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\DTOs\Web\AdminMenuGroupData;
use App\DTOs\Web\AdminMenuItemData;
use App\Enums\AdminMenuPermissionStrategy;
use App\Models\AdminMenuGroup;
use App\Models\AdminMenuItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Throwable;

final class AdminMenuBuilderService
{
    public function __construct(
        private readonly AdminMenuVersionService $versionService,
        private readonly AdminMenuEventLogger $eventLogger,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function buildForUser(User $user): array
    {
        if (! $user->is_active) {
            return [];
        }

        $version = $this->versionService->currentVersion();
        $roleId = $user->role_id !== null ? (int) $user->role_id : 0;
        $cacheKey = sprintf('admin_menu:web:user:%d:role:%d:version:%d', $user->id, $roleId, $version);

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            fn (): array => $this->buildFresh($user)
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildFresh(User $user): array
    {
        $permissionCodes = $this->effectivePermissionCodes($user);
        $hasAdminFull = in_array(PermissionRegistry::ADMIN_FULL, $permissionCodes, true);

        /** @var Collection<int, AdminMenuGroup> $groups */
        $groups = AdminMenuGroup::query()
            ->where('active', true)
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        /** @var Collection<int, AdminMenuItem> $items */
        $items = AdminMenuItem::query()
            ->with(['permissions' => static fn ($query) => $query->where('permissions.active', true)])
            ->where('active', true)
            ->orderBy('order')
            ->orderBy('title')
            ->get();

        $itemsById = $items->keyBy('id');
        $childrenByParent = $items->groupBy(static fn (AdminMenuItem $item): int => (int) ($item->parent_id ?? 0));
        $cyclicItemIds = $this->detectCyclicItemIds($itemsById);
        $builtGroups = [];

        foreach ($groups as $group) {
            $groupItems = [];

            foreach ($items as $item) {
                if ((int) $item->admin_menu_group_id !== (int) $group->id || $item->parent_id !== null) {
                    continue;
                }

                $builtItem = $this->buildItem(
                    item: $item,
                    childrenByParent: $childrenByParent,
                    permissionCodes: $permissionCodes,
                    hasAdminFull: $hasAdminFull,
                    cyclicItemIds: $cyclicItemIds,
                    path: [],
                );

                if ($builtItem !== null) {
                    $groupItems[] = $builtItem;
                }
            }

            if ($groupItems === []) {
                continue;
            }

            $builtGroups[] = (new AdminMenuGroupData(
                code: $group->code,
                title: $this->resolveTitle($group->translation_key, $group->title),
                icon: $group->icon,
                order: $group->order,
                items: $groupItems,
            ))->toArray();
        }

        return $builtGroups;
    }

    /**
     * @return list<string>
     */
    private function effectivePermissionCodes(User $user): array
    {
        if ($user->role_id === null) {
            return [];
        }

        $role = Role::query()
            ->with(['permissions' => static fn ($query) => $query->where('permissions.active', true)])
            ->whereKey($user->role_id)
            ->where('active', true)
            ->first();

        if (! $role instanceof Role) {
            return [];
        }

        $codes = [];

        foreach ($role->permissions as $permission) {
            if ($permission instanceof Permission) {
                $codes[] = $permission->code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * @param  Collection<int, Collection<int, AdminMenuItem>>  $childrenByParent
     * @param  list<string>  $permissionCodes
     * @param  array<int, true>  $cyclicItemIds
     * @param  list<int>  $path
     * @return array<string, mixed>|null
     */
    private function buildItem(
        AdminMenuItem $item,
        Collection $childrenByParent,
        array $permissionCodes,
        bool $hasAdminFull,
        array $cyclicItemIds,
        array $path,
    ): ?array {
        $itemId = (int) $item->id;

        if (isset($cyclicItemIds[$itemId]) || in_array($itemId, $path, true)) {
            $this->eventLogger->log(
                event: 'admin_menu.cycle_detected',
                level: 'warning',
                entityType: AdminMenuItem::class,
                entityId: $itemId,
                context: ['code' => $item->code, 'path' => $path],
                message: 'Ciclo hierárquico detectado durante montagem do menu administrativo.',
            );

            return null;
        }

        $children = [];
        $nextPath = [...$path, $itemId];

        foreach ($childrenByParent->get($itemId, collect()) as $child) {
            if (! $child instanceof AdminMenuItem) {
                continue;
            }

            $builtChild = $this->buildItem(
                item: $child,
                childrenByParent: $childrenByParent,
                permissionCodes: $permissionCodes,
                hasAdminFull: $hasAdminFull,
                cyclicItemIds: $cyclicItemIds,
                path: $nextPath,
            );

            if ($builtChild !== null) {
                $children[] = $builtChild;
            }
        }

        $url = $this->urlForItem($item);
        $hasVisibleChildren = $children !== [];
        $hasDirectPermission = $hasAdminFull || $this->itemAllowsPermissionCodes($item, $permissionCodes);

        if (! $hasVisibleChildren && ! ($hasDirectPermission && $url !== null)) {
            return null;
        }

        return (new AdminMenuItemData(
            code: $item->code,
            title: $this->resolveTitle($item->translation_key, $item->title),
            routeName: $item->route_name,
            url: $url,
            icon: $item->icon,
            order: $item->order,
            active: $this->isItemActive($item, $children),
            opensInNewTab: $item->opens_in_new_tab,
            children: $children,
        ))->toArray();
    }

    /**
     * @param  list<array<string, mixed>>  $children
     */
    private function isItemActive(AdminMenuItem $item, array $children): bool
    {
        foreach ($children as $child) {
            if (($child['active'] ?? false) === true) {
                return true;
            }
        }

        if (is_string($item->active_route_pattern) && $item->active_route_pattern !== '') {
            return request()->routeIs($item->active_route_pattern);
        }

        if (is_string($item->route_name) && $item->route_name !== '') {
            return request()->routeIs($item->route_name) || request()->routeIs($item->route_name.'.*');
        }

        return false;
    }

    /**
     * @param  list<string>  $permissionCodes
     */
    private function itemAllowsPermissionCodes(AdminMenuItem $item, array $permissionCodes): bool
    {
        $itemPermissionCodes = [];

        foreach ($item->permissions as $permission) {
            if ($permission instanceof Permission) {
                $itemPermissionCodes[] = $permission->code;
            }
        }

        if ($itemPermissionCodes === []) {
            return false;
        }

        if ($item->permission_strategy === AdminMenuPermissionStrategy::ALL) {
            return count(array_diff($itemPermissionCodes, $permissionCodes)) === 0;
        }

        return count(array_intersect($itemPermissionCodes, $permissionCodes)) > 0;
    }

    private function urlForItem(AdminMenuItem $item): ?string
    {
        if (! is_string($item->route_name) || $item->route_name === '') {
            return null;
        }

        if (! Route::has($item->route_name)) {
            $this->eventLogger->log(
                event: 'admin_menu.route_not_found',
                level: 'warning',
                entityType: AdminMenuItem::class,
                entityId: (int) $item->id,
                context: ['code' => $item->code, 'route_name' => $item->route_name],
                message: 'Route name configurado no menu administrativo não existe.',
            );

            return null;
        }

        try {
            return route($item->route_name);
        } catch (Throwable $throwable) {
            $this->eventLogger->log(
                event: 'admin_menu.route_generation_failed',
                level: 'error',
                entityType: AdminMenuItem::class,
                entityId: (int) $item->id,
                context: ['code' => $item->code, 'route_name' => $item->route_name],
                message: 'Falha ao gerar URL do item de menu administrativo.',
                throwable: $throwable,
            );

            return null;
        }
    }

    private function resolveTitle(?string $translationKey, string $fallback): string
    {
        if (is_string($translationKey) && $translationKey !== '' && trans()->has($translationKey)) {
            $translation = trans($translationKey);

            if (is_string($translation)) {
                return $translation;
            }
        }

        return $fallback;
    }

    /**
     * @param  Collection<int, AdminMenuItem>  $itemsById
     * @return array<int, true>
     */
    private function detectCyclicItemIds(Collection $itemsById): array
    {
        $cyclicItemIds = [];

        foreach ($itemsById as $item) {
            $path = [];
            $current = $item;

            while ($current instanceof AdminMenuItem && $current->parent_id !== null) {
                $currentId = (int) $current->id;

                if (in_array($currentId, $path, true)) {
                    foreach ($path as $pathItemId) {
                        $cyclicItemIds[$pathItemId] = true;
                    }

                    $this->eventLogger->log(
                        event: 'admin_menu.cycle_detected',
                        level: 'warning',
                        entityType: AdminMenuItem::class,
                        entityId: $currentId,
                        context: ['path' => $path],
                        message: 'Ciclo hierárquico detectado na configuração do menu administrativo.',
                    );

                    break;
                }

                $path[] = $currentId;
                $current = $itemsById->get((int) $current->parent_id);
            }
        }

        return $cyclicItemIds;
    }
}
