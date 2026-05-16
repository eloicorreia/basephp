<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTO\Admin\SyncRolePermissionsDTO;
use App\Exceptions\BusinessException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RolePermissionService
{
    public function __construct(
        private readonly LogPersistenceService $logPersistenceService
    ) {}

    public function sync(Role $role, SyncRolePermissionsDTO $dto): Role
    {
        return DB::transaction(function () use ($role, $dto): Role {
            $authenticatedUser = auth()->user();
            $role = Role::query()
                ->with('permissions')
                ->whereKey($role->id)
                ->lockForUpdate()
                ->firstOrFail();
            $newPermissions = Permission::query()
                ->whereIn('id', $dto->permissionIds)
                ->lockForUpdate()
                ->get();

            $before = $this->permissionSnapshot($role);
            $this->ensureSyncKeepsPermissionAdministration($role, $newPermissions, $authenticatedUser);

            $syncPayload = [];
            $assignedAt = now();
            $assignedBy = AuthenticatedUserId::resolve();

            foreach ($dto->permissionIds as $permissionId) {
                $syncPayload[$permissionId] = [
                    'assigned_by' => $assignedBy,
                    'assigned_at' => $assignedAt,
                ];
            }

            $role->permissions()->sync($syncPayload);
            $role->load('permissions');
            $after = $this->permissionSnapshot($role);

            $this->logPersistenceService->logAudit(
                action: 'role.permissions_synced',
                auditableType: Role::class,
                auditableId: $role->id,
                beforeData: ['permissions' => $before],
                afterData: ['permissions' => $after],
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User
                    ? $authenticatedUser->role?->code
                    : null,
            );

            return $role;
        });
    }

    /**
     * @param  Collection<int, Permission>  $newPermissions
     */
    private function ensureSyncKeepsPermissionAdministration(Role $role, Collection $newPermissions, mixed $authenticatedUser): void
    {
        $newPermissionCodes = [];

        foreach ($newPermissions as $permission) {
            if ($permission->active) {
                $newPermissionCodes[] = $permission->code;
            }
        }

        if (
            ! in_array(PermissionRegistry::ADMIN_FULL, $newPermissionCodes, true)
            && $this->activeRolesWithPermissionAfterSync(PermissionRegistry::ADMIN_FULL, $role) === 0
        ) {
            throw new BusinessException('Não é permitido remover admin.full da última role administrativa operacional.');
        }

        if (
            ! in_array(PermissionRegistry::ROLES_WRITE, $newPermissionCodes, true)
            && $this->activeRolesWithPermissionAfterSync(PermissionRegistry::ROLES_WRITE, $role) === 0
        ) {
            throw new BusinessException('Não é permitido remover roles.write de todas as roles.');
        }

        if (
            ! in_array(PermissionRegistry::PERMISSIONS_WRITE, $newPermissionCodes, true)
            && $this->activeRolesWithPermissionAfterSync(PermissionRegistry::PERMISSIONS_WRITE, $role) === 0
        ) {
            throw new BusinessException('Não é permitido remover permissions.write de todas as roles.');
        }

        if ($this->activeUsersThatCanAdminPermissionsAfterSync($role, $newPermissionCodes) === 0) {
            throw new BusinessException('Não é permitido deixar o sistema sem usuário ativo capaz de administrar permissões.');
        }

        if (
            $authenticatedUser instanceof User
            && $authenticatedUser->role_id === $role->id
            && ! $this->containsPermissionAdministrationAccess($newPermissionCodes)
        ) {
            throw new BusinessException('Não é permitido remover da própria role a capacidade de administrar permissões.');
        }
    }

    private function activeRolesWithPermissionAfterSync(string $permissionCode, Role $changedRole): int
    {
        $lockedRoles = DB::select(
            'select roles.id
            from roles
            inner join role_permissions on role_permissions.role_id = roles.id
            inner join permissions on permissions.id = role_permissions.permission_id
            where roles.active = true
            and roles.id <> ?
            and permissions.active = true
            and permissions.code = ?
            for update of roles',
            [$changedRole->id, $permissionCode]
        );

        return count($lockedRoles);
    }

    /**
     * @param  list<string>  $newPermissionCodes
     */
    private function activeUsersThatCanAdminPermissionsAfterSync(Role $changedRole, array $newPermissionCodes): int
    {
        $lockedRoles = DB::select(
            'select roles.id
            from roles
            inner join role_permissions on role_permissions.role_id = roles.id
            inner join permissions on permissions.id = role_permissions.permission_id
            where roles.active = true
            and roles.id <> ?
            and permissions.active = true
            and permissions.code in (?, ?, ?)
            for update of roles',
            [
                $changedRole->id,
                PermissionRegistry::ADMIN_FULL,
                PermissionRegistry::ROLES_WRITE,
                PermissionRegistry::PERMISSIONS_WRITE,
            ]
        );

        $roleIds = [];

        foreach ($lockedRoles as $row) {
            $roleIds[] = (int) data_get($row, 'id');
        }

        if ($this->containsPermissionAdministrationAccess($newPermissionCodes)) {
            $roleIds[] = $changedRole->id;
        }

        if ($roleIds === []) {
            return 0;
        }

        $uniqueRoleIds = array_values(array_unique($roleIds));
        $placeholders = implode(', ', array_fill(0, count($uniqueRoleIds), '?'));

        $lockedUsers = DB::select(
            'select id from users where is_active = true and role_id in ('.$placeholders.') for update',
            $uniqueRoleIds
        );

        return count($lockedUsers);
    }

    /**
     * @param  list<string>  $permissionCodes
     */
    private function containsPermissionAdministrationAccess(array $permissionCodes): bool
    {
        return $this->containsAnyAdministrationPermission($permissionCodes)
            || in_array(PermissionRegistry::PERMISSIONS_WRITE, $permissionCodes, true);
    }

    /**
     * @param  list<string>  $permissionCodes
     */
    private function containsAnyAdministrationPermission(array $permissionCodes): bool
    {
        return in_array(PermissionRegistry::ADMIN_FULL, $permissionCodes, true)
            || in_array(PermissionRegistry::ROLES_WRITE, $permissionCodes, true);
    }

    /**
     * @return list<array{id: int, code: string, name: string, context: string, is_sensitive: bool}>
     */
    private function permissionSnapshot(Role $role): array
    {
        $permissions = [];

        foreach ($role->permissions->sortBy('code') as $permission) {
            if (! $permission instanceof Permission) {
                continue;
            }

            $permissions[] = [
                'id' => $permission->id,
                'code' => $permission->code,
                'name' => $permission->name,
                'context' => $permission->context,
                'is_sensitive' => $permission->is_sensitive,
            ];
        }

        return $permissions;
    }
}
