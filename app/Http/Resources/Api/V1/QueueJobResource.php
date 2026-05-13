<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QueueJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        if (is_array($resource)) {
            return [
                'id' => $resource['id'] ?? null,
                'queue' => $resource['queue'] ?? null,
                'attempts' => $resource['attempts'] ?? null,
                'reserved_at' => $resource['reserved_at'] ?? null,
                'available_at' => $resource['available_at'] ?? null,
                'created_at' => $resource['created_at'] ?? null,
                'payload_preview' => $resource['payload_preview'] ?? null,
            ];
        }

        if (! $resource instanceof Job) {
            return [];
        }

        return [
            'id' => $resource->id,
            'queue' => $resource->queue,
            'attempts' => $resource->attempts,
            'reserved_at' => $resource->reserved_at,
            'available_at' => $resource->available_at,
            'created_at' => $resource->created_at,
            'payload_preview' => null,
        ];
    }
}
