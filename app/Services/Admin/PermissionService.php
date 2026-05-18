<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTO\Admin\CreatePermissionDTO;
use App\DTO\Admin\UpdatePermissionDTO;
use App\Exceptions\BusinessException;
use App\Models\Permission;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function __construct(
        private readonly LogPersistenceService $logPersistenceService
    ) {}

    /**
     * @return LengthAwarePaginator<int, Permission>
     */
    public function paginateForAdmin(bool $activeOnly = true, ?string $context = null, int $perPage = 50): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, (int) config('tenant.runtime.max_items_per_page', 100)));

        return Permission::query()
            ->when($activeOnly, static fn ($query) => $query->where('active', true))
            ->when($context !== null, static fn ($query) => $query->where('context', $context))
            ->orderBy('context')
            ->orderBy('code')
            ->paginate($perPage);
    }

    public function create(CreatePermissionDTO $dto): Permission
    {
        return DB::transaction(function () use ($dto): Permission {
            $permission = Permission::query()->create([
                'code' => $dto->code,
                'name' => $dto->name,
                'description' => $dto->description,
                'group' => $dto->group,
                'context' => $dto->context,
                'is_system' => false,
                'is_sensitive' => $dto->isSensitive,
                'active' => true,
            ]);

            $this->audit('permission.created', $permission, null, $this->snapshot($permission));

            return $permission;
        });
    }

    public function update(Permission $permission, UpdatePermissionDTO $dto): Permission
    {
        return DB::transaction(function () use ($permission, $dto): Permission {
            $permission = Permission::query()->whereKey($permission->id)->lockForUpdate()->firstOrFail();
            $before = $this->snapshot($permission);

            if ($permission->is_system) {
                $permission->forceFill([
                    'name' => $dto->name,
                    'description' => $dto->description,
                    'group' => $dto->group,
                ])->save();
            } else {
                $permission->forceFill([
                    'name' => $dto->name,
                    'description' => $dto->description,
                    'group' => $dto->group,
                    'context' => $dto->context,
                    'is_sensitive' => $dto->isSensitive,
                ])->save();
            }

            $after = $this->snapshot($permission);
            $this->audit('permission.updated', $permission, $before, $after);

            return $permission->refresh();
        });
    }

    public function enable(Permission $permission): Permission
    {
        return $this->setActive($permission, true);
    }

    public function disable(Permission $permission): Permission
    {
        return $this->setActive($permission, false);
    }

    private function setActive(Permission $permission, bool $active): Permission
    {
        return DB::transaction(function () use ($permission, $active): Permission {
            $permission = Permission::query()->whereKey($permission->id)->lockForUpdate()->firstOrFail();

            if ($permission->is_system && ! $active) {
                throw new BusinessException('Permissões de sistema não podem ser desativadas.');
            }

            $before = $this->snapshot($permission);
            $permission->forceFill(['active' => $active])->save();
            $after = $this->snapshot($permission);

            $this->audit($active ? 'permission.enabled' : 'permission.disabled', $permission, $before, $after);

            return $permission->refresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'code' => $permission->code,
            'name' => $permission->name,
            'description' => $permission->description,
            'group' => $permission->group,
            'context' => $permission->context,
            'is_system' => $permission->is_system,
            'is_sensitive' => $permission->is_sensitive,
            'active' => $permission->active,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(string $action, Permission $permission, ?array $before, ?array $after): void
    {
        $authenticatedUser = auth()->user();

        $this->logPersistenceService->logAudit(
            action: $action,
            auditableType: Permission::class,
            auditableId: $permission->id,
            beforeData: $before,
            afterData: $after,
            userId: AuthenticatedUserId::resolve(),
            userRole: $authenticatedUser instanceof User
                ? $authenticatedUser->role?->code
                : null,
        );
    }
}
