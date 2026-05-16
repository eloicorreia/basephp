<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Role;
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

        return [
            'id' => $role->id,
            'code' => $role->code,
            'name' => $role->name,
            'active' => $role->active,
            'users_count' => $role->users_count,
            'created_at' => $role->created_at,
            'updated_at' => $role->updated_at,
        ];
    }
}
