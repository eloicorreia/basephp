<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Services\TenantSettings\TenantRuntimeSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
        $runtimeSettings = app(TenantRuntimeSettings::class);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'code' => $user->role->code,
                'name' => $user->role->name,
            ] : null,
            'created_at' => $runtimeSettings->isoDateTime($user->created_at),
            'created_at_formatted' => $runtimeSettings->formatDateTime($user->created_at),
        ];
    }
}
