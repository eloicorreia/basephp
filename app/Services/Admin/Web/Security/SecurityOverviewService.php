<?php

declare(strict_types=1);

namespace App\Services\Admin\Web\Security;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

final class SecurityOverviewService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'metrics' => [
                'users' => User::query()->count(),
                'active_users' => User::query()->where('is_active', true)->count(),
                'roles' => Role::query()->count(),
                'permissions' => Permission::query()->count(),
            ],
            'roles' => $this->rolesTree(),
        ];
    }

    /**
     * @return Collection<int, Role>
     */
    private function rolesTree(): Collection
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->with(['permissions' => static fn ($query) => $query->orderBy('group')->orderBy('code')])
            ->orderBy('name')
            ->get();
    }
}
