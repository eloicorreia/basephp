<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Permission;
use App\Services\TenantSettings\TenantRuntimeSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Permission) {
            return [];
        }

        $permission = $this->resource;
        $runtimeSettings = app(TenantRuntimeSettings::class);

        return [
            'id' => $permission->id,
            'code' => $permission->code,
            'name' => $permission->name,
            'description' => $permission->description,
            'group' => $permission->group,
            'context' => $permission->context,
            'is_system' => $permission->is_system,
            'is_sensitive' => $permission->is_sensitive,
            'active' => $permission->active,
            'created_at' => $runtimeSettings->formatDateTime($permission->created_at),
            'updated_at' => $runtimeSettings->formatDateTime($permission->updated_at),
        ];
    }
}
