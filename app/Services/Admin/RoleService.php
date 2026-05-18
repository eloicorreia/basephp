<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleService
{
    private const DEFAULT_PER_PAGE = 15;

    private const MAX_PER_PAGE = 100;

    /**
     * @var array<string, string>
     */
    private const SORT_COLUMNS = [
        'id' => 'id',
        'code' => 'code',
        'name' => 'name',
        'created_at' => 'created_at',
    ];

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginateForAdmin(
        bool $activeOnly = true,
        ?int $perPage = null,
        ?string $sort = null,
        ?string $direction = null,
    ): LengthAwarePaginator {
        $perPage = $this->normalizePerPage($perPage);
        $sortColumn = $this->normalizeSort($sort);
        $sortDirection = $this->normalizeDirection($direction);

        return Role::query()
            ->withCount('users')
            ->withCount('permissions')
            ->when(
                $activeOnly,
                static fn ($query) => $query->where('active', true)
            )
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage);
    }

    public function loadDetails(Role $role): Role
    {
        return $role->loadCount('users');
    }

    private function normalizePerPage(?int $perPage): int
    {
        if ($perPage === null || $perPage < 1) {
            return (int) config('tenant.runtime.default_items_per_page', self::DEFAULT_PER_PAGE);
        }

        return min($perPage, (int) config('tenant.runtime.max_items_per_page', self::MAX_PER_PAGE));
    }

    private function normalizeSort(?string $sort): string
    {
        if ($sort === null || ! array_key_exists($sort, self::SORT_COLUMNS)) {
            return self::SORT_COLUMNS['name'];
        }

        return self::SORT_COLUMNS[$sort];
    }

    private function normalizeDirection(?string $direction): string
    {
        return $direction === 'desc' ? 'desc' : 'asc';
    }
}
