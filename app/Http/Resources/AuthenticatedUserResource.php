<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tenantCode = $request->header('X-Tenant-Id');

        $tenantUser = $this->tenantUsers->first(
            fn ($item) => $item->tenant?->code === $tenantCode
        );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'global_role' => [
                'id' => $this->role?->id,
                'code' => $this->role?->code,
                'name' => $this->role?->name,
            ],
            'tenant_role' => $tenantUser?->role ? [
                'id' => $tenantUser->role->id,
                'code' => $tenantUser->role->code,
                'name' => $tenantUser->role->name,
            ] : null,
        ];
    }
}