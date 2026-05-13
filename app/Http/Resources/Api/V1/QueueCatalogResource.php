<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QueueCatalogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = is_array($this->resource) ? $this->resource : [];

        return [
            'name' => $resource['name'] ?? null,
            'purpose' => $resource['purpose'] ?? null,
            'retry_limit' => $resource['retry_limit'] ?? null,
            'timeout_seconds' => $resource['timeout_seconds'] ?? null,
        ];
    }
}
