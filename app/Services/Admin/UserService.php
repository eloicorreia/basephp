<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\DTO\Admin\AssignUserRoleDTO;
use App\DTO\Admin\CreateUserDTO;
use App\Models\User;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
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
            $beforeRoleId = $user->role_id;

            $user->forceFill([
                'role_id' => $dto->roleId,
            ])->save();

            $this->logPersistenceService->logAudit(
                action: 'user.role_assigned',
                auditableType: User::class,
                auditableId: $user->id,
                beforeData: [
                    'role_id' => $beforeRoleId,
                ],
                afterData: [
                    'role_id' => $user->role_id,
                ],
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User
                    ? $authenticatedUser->role?->code
                    : null,
            );

            return $user->refresh();
        });
    }
}
