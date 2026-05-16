<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTO\Admin\SyncRolePermissionsDTO;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
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

            $before = $this->permissionSnapshot($role);
            $role->permissions()->sync($dto->permissionIds);
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
     * @return list<array{id: int, code: string}>
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
            ];
        }

        return $permissions;
    }
}
