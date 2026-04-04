<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant' => [
                'id' => $this->tenant?->id,
                'code' => $this->tenant?->code,
                'name' => $this->tenant?->name,
                'schema_name' => $this->tenant?->schema_name,
            ],
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ],
            'role' => [
                'id' => $this->role?->id,
                'code' => $this->role?->code,
                'name' => $this->role?->name,
            ],
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}