<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'role' => $this->role ? [
                'id' => $this->role->id,
                'code' => $this->role->code,
                'name' => $this->role->name,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}