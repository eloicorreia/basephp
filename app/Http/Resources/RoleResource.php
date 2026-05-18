<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Role;
use App\Services\TenantSettings\TenantRuntimeSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Role) {
            return [];
        }

        $role = $this->resource;
        $runtimeSettings = app(TenantRuntimeSettings::class);

        return [
            'id' => $role->id,
            'code' => $role->code,
            'name' => $role->name,
            'active' => $role->active,
            'users_count' => $role->users_count,
            'permissions_count' => $role->permissions_count,
            'created_at' => $runtimeSettings->isoDateTime($role->created_at),
            'updated_at' => $runtimeSettings->isoDateTime($role->updated_at),
            'created_at_formatted' => $runtimeSettings->formatDateTime($role->created_at),
            'updated_at_formatted' => $runtimeSettings->formatDateTime($role->updated_at),
        ];
    }
}
