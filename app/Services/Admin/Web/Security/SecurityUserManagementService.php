<?php

declare(strict_types=1);

namespace App\Services\Admin\Web\Security;

use App\DTO\Admin\AssignUserRoleDTO;
use App\DTO\Admin\CreateUserDTO;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\UserService;
use App\Services\Logging\LogPersistenceService;
use App\Support\Auth\AuthenticatedUserId;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class SecurityUserManagementService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly LogPersistenceService $logPersistenceService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $search = null, ?int $roleId = null, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->with('role')
            ->when($search !== null && $search !== '', static function ($query) use ($search): void {
                $query->where(static function ($inner) use ($search): void {
                    $inner->where('name', 'ilike', '%'.$search.'%')
                        ->orWhere('email', 'ilike', '%'.$search.'%');
                });
            })
            ->when($roleId !== null, static fn ($query) => $query->where('role_id', $roleId))
            ->when($status === 'active', static fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', static fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(max(1, min($perPage, 100)))
            ->withQueryString();
    }

    public function create(CreateUserDTO $dto): User
    {
        return $this->userService->create($dto);
    }

    public function update(User $user, string $name, bool $isActive, bool $mustChangePassword): User
    {
        return DB::transaction(function () use ($user, $name, $isActive, $mustChangePassword): User {
            $authenticatedUser = auth()->user();
            $user = User::query()->with('role')->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $before = [
                'name' => $user->name,
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
            ];

            $user->forceFill([
                'name' => $name,
                'is_active' => $isActive,
                'must_change_password' => $mustChangePassword,
            ])->save();

            $this->logPersistenceService->logAudit(
                action: 'web_security.user_updated',
                auditableType: User::class,
                auditableId: $user->id,
                beforeData: $before,
                afterData: [
                    'name' => $user->name,
                    'is_active' => $user->is_active,
                    'must_change_password' => $user->must_change_password,
                ],
                userId: AuthenticatedUserId::resolve(),
                userRole: $authenticatedUser instanceof User ? $authenticatedUser->role?->code : null,
            );

            return $user->refresh();
        });
    }

    public function assignRole(User $user, int $roleId): User
    {
        return $this->userService->assignRole($user, new AssignUserRoleDTO($roleId));
    }

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function activeRoles(): LengthAwarePaginator
    {
        return Role::query()->where('active', true)->orderBy('name')->paginate(100);
    }
}
