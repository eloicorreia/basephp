<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService
{
    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginateForAdmin(bool $activeOnly = true): LengthAwarePaginator
    {
        return Role::query()
            ->withCount('users')
            ->when(
                $activeOnly,
                static fn ($query) => $query->where('active', true)
            )
            ->orderBy('name')
            ->paginate(15);
    }

    public function loadDetails(Role $role): Role
    {
        return $role->loadCount('users');
    }
}
