<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof User) {
            return [];
        }

        $user = $this->resource;
        $tenantCode = $request->header('X-Tenant-Id');

        $tenantUser = $user->tenantUsers->first(
            fn ($item) => $item->tenant?->code === $tenantCode
        );

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'global_role' => [
                'id' => $user->role?->id,
                'code' => $user->role?->code,
                'name' => $user->role?->name,
            ],
            'tenant_role' => $tenantUser?->role ? [
                'id' => $tenantUser->role->id,
                'code' => $tenantUser->role->code,
                'name' => $tenantUser->role->name,
            ] : null,
            'permissions' => $user->role?->permissions
                ? $user->role->permissions
                    ->where('active', true)
                    ->sortBy('code')
                    ->pluck('code')
                    ->values()
                    ->all()
                : [],
        ];
    }
}
