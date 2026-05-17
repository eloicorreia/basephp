<?php

declare(strict_types=1);

namespace App\Services\Admin\Web\Security;

use App\DTO\Admin\SyncRolePermissionsDTO;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\RolePermissionService;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class SecurityRoleManagementService
{
    public function __construct(
        private readonly RolePermissionService $rolePermissionService,
        private readonly LogPersistenceService $logPersistenceService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginate(bool $includeInactive = true, int $perPage = 20): LengthAwarePaginator
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->when(! $includeInactive, static fn ($query) => $query->where('active', true))
            ->orderBy('name')
            ->paginate(max(1, min($perPage, 100)))
            ->withQueryString();
    }

    public function create(string $code, string $name, bool $active): Role
    {
        return DB::transaction(function () use ($code, $name, $active): Role {
            $role = Role::query()->create([
                'code' => $code,
                'name' => $name,
                'active' => $active,
            ]);

            $this->audit('web_security.role_created', $role, null, $this->snapshot($role));

            return $role;
        });
    }

    public function update(Role $role, string $name, bool $active): Role
    {
        return DB::transaction(function () use ($role, $name, $active): Role {
            $role = Role::query()->whereKey($role->id)->lockForUpdate()->firstOrFail();
            $before = $this->snapshot($role);

            $role->forceFill([
                'name' => $name,
                'active' => $active,
            ])->save();

            $this->audit('web_security.role_updated', $role, $before, $this->snapshot($role));

            return $role->refresh();
        });
    }

    /**
     * @param  list<int>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        return $this->rolePermissionService->sync($role, new SyncRolePermissionsDTO($permissionIds));
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Role $role): array
    {
        return [
            'id' => $role->id,
            'code' => $role->code,
            'name' => $role->name,
            'active' => $role->active,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(string $action, Role $role, ?array $before, ?array $after): void
    {
        $authenticatedUser = auth()->user();

        $this->logPersistenceService->logAudit(
            action: $action,
            auditableType: Role::class,
            auditableId: $role->id,
            beforeData: $before,
            afterData: $after,
            userId: AuthenticatedUserId::resolve(),
            userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
        );
    }
}
