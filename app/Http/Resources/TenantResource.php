<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Tenant) {
            return [];
        }

        $tenant = $this->resource;

        return [
            'id' => $tenant->id,
            'code' => $tenant->code,
            'name' => $tenant->name,
            'schema_name' => $tenant->schema_name,
            'status' => $tenant->status,
            'is_active' => $tenant->status === Tenant::STATUS_ACTIVE,
            'created_at' => $tenant->created_at,
            'updated_at' => $tenant->updated_at,
        ];
    }
}
