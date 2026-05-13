<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\TenantUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof TenantUser) {
            return [];
        }

        $tenantUser = $this->resource;

        return [
            'id' => $tenantUser->id,
            'tenant' => [
                'id' => $tenantUser->tenant?->id,
                'code' => $tenantUser->tenant?->code,
                'name' => $tenantUser->tenant?->name,
                'schema_name' => $tenantUser->tenant?->schema_name,
            ],
            'user' => [
                'id' => $tenantUser->user?->id,
                'name' => $tenantUser->user?->name,
                'email' => $tenantUser->user?->email,
            ],
            'role' => [
                'id' => $tenantUser->role?->id,
                'code' => $tenantUser->role?->code,
                'name' => $tenantUser->role?->name,
            ],
            'is_active' => (bool) $tenantUser->is_active,
            'created_at' => $tenantUser->created_at,
            'updated_at' => $tenantUser->updated_at,
        ];
    }
}
