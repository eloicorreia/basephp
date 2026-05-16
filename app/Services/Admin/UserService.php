<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTO\Admin\AssignUserRoleDTO;
use App\DTO\Admin\CreateUserDTO;
use App\Enums\RoleCode;
use App\Models\Role;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly LogPersistenceService $logPersistenceService
    ) {}

    public function create(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto): User {
            $authenticatedUser = auth()->user();
            $user = User::query()->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password,
                'role_id' => $dto->roleId,
                'is_active' => $dto->isActive,
                'must_change_password' => $dto->mustChangePassword,
            ]);

            $this->logPersistenceService->logAudit(
                action: 'user.created',
                auditableType: User::class,
                auditableId: $user->id,
                beforeData: null,
                afterData: [
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'is_active' => $user->is_active,
                ],
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User
                    ? $authenticatedUser->role?->code
                    : null,
            );

            return $user;
        });
    }

    public function assignRole(User $user, AssignUserRoleDTO $dto): User
    {
        return DB::transaction(function () use ($user, $dto): User {
            $authenticatedUser = auth()->user();
            $user->loadMissing('role');
            $newRole = Role::query()->findOrFail($dto->roleId);

            $this->ensureRoleCanBeChanged($user, $newRole, $authenticatedUser);

            $beforeRole = $user->role;

            $user->forceFill([
                'role_id' => $newRole->id,
            ])->save();

            $this->logPersistenceService->logAudit(
                action: 'user.role_assigned',
                auditableType: User::class,
                auditableId: $user->id,
                beforeData: $this->roleAuditSnapshot($beforeRole),
                afterData: $this->roleAuditSnapshot($newRole),
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User
                    ? $authenticatedUser->role?->code
                    : null,
            );

            return $user->refresh();
        });
    }

    private function ensureRoleCanBeChanged(User $targetUser, Role $newRole, mixed $authenticatedUser): void
    {
        if (
            $this->isActiveAdmin($targetUser)
            && $newRole->code !== RoleCode::ADMIN->value
            && $this->activeAdminUsersCount() <= 1
        ) {
            throw new AuthorizationException('Não é permitido remover o último administrador ativo.');
        }

        if (
            $authenticatedUser instanceof User
            && $authenticatedUser->id === $targetUser->id
        ) {
            throw new AuthorizationException('Não é permitido alterar a própria role.');
        }
    }

    private function activeAdminUsersCount(): int
    {
        $adminRoleId = Role::query()
            ->where('code', RoleCode::ADMIN->value)
            ->where('active', true)
            ->value('id');

        if ($adminRoleId === null) {
            return 0;
        }

        return User::query()
            ->where('is_active', true)
            ->where('role_id', $adminRoleId)
            ->count();
    }

    private function isActiveAdmin(User $user): bool
    {
        return $user->is_active
            && $user->role?->active === true
            && $user->role->code === RoleCode::ADMIN->value;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function roleAuditSnapshot(?Role $role): ?array
    {
        if ($role === null) {
            return null;
        }

        return [
            'role_id' => $role->id,
            'role_code' => $role->code,
            'role_name' => $role->name,
        ];
    }
}
