<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QueueJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;
        $payloadPreview = is_array($resource) ? $resource['payload_preview'] : null;

        return [
            'id' => is_array($resource) ? $resource['id'] : $this->id,
            'queue' => is_array($resource) ? $resource['queue'] : $this->queue,
            'attempts' => is_array($resource) ? $resource['attempts'] : $this->attempts,
            'reserved_at' => is_array($resource) ? $resource['reserved_at'] : $this->reserved_at,
            'available_at' => is_array($resource) ? $resource['available_at'] : $this->available_at,
            'created_at' => is_array($resource) ? $resource['created_at'] : $this->created_at,
            'payload_preview' => $payloadPreview,
        ];
    }
}