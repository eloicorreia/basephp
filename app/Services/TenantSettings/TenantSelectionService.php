<?php

declare(strict_types=1);

namespace App\Services\TenantSettings;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\Auth\PermissionRegistry;
use Illuminate\Database\Eloquent\Collection;

final class TenantSelectionService
{
    /**
     * @return Collection<int, Tenant>
     */
    public function availableTenants(User $user): Collection
    {
        if ($user->hasPermission(PermissionRegistry::ADMIN_FULL)) {
            return Tenant::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        $tenantIds = TenantUser::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('tenant_id');

        return Tenant::query()
            ->where('status', 'active')
            ->whereIn('id', $tenantIds)
            ->orderBy('name')
            ->get();
    }

    public function resolveForUser(User $user, ?string $tenantCode): ?Tenant
    {
        if ($tenantCode === null || trim($tenantCode) === '') {
            return $this->availableTenants($user)->first();
        }

        $query = Tenant::query()
            ->where('code', trim($tenantCode))
            ->where('status', 'active');

        if (! $user->hasPermission(PermissionRegistry::ADMIN_FULL)) {
            $tenantIds = TenantUser::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->pluck('tenant_id');

            $query->whereIn('id', $tenantIds);
        }

        return $query->first();
    }
}
