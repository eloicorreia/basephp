<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_active' => $this->is_active,
            'tenant' => $this->tenant ? [
                'id' => $this->tenant->id,
                'code' => $this->tenant->code,
                'name' => $this->tenant->name,
                'schema_name' => $this->tenant->schema_name,
            ] : null,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'role' => $this->role ? [
                'id' => $this->role->id,
                'code' => $this->role->code,
                'name' => $this->role->name,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}