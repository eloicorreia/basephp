<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QueueSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = is_array($this->resource) ? $this->resource : [];

        return [
            'queue' => $resource['queue'] ?? null,
            'pending_jobs' => $resource['pending_jobs'] ?? null,
            'running_jobs' => $resource['running_jobs'] ?? null,
            'failed_jobs' => $resource['failed_jobs'] ?? null,
            'generated_at' => $resource['generated_at'] ?? null,
        ];
    }
}
