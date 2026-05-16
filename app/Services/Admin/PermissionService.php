<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PermissionService
{
    /**
     * @return LengthAwarePaginator<int, Permission>
     */
    public function paginateForAdmin(bool $activeOnly = true, ?string $context = null, int $perPage = 50): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 100));

        return Permission::query()
            ->when($activeOnly, static fn ($query) => $query->where('active', true))
            ->when($context !== null, static fn ($query) => $query->where('context', $context))
            ->orderBy('context')
            ->orderBy('code')
            ->paginate($perPage);
    }
}
