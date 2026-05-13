<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\FailedJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FailedJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof FailedJob) {
            return [];
        }

        $failedJob = $this->resource;

        return [
            'id' => $failedJob->id,
            'uuid' => $failedJob->uuid,
            'connection' => $failedJob->connection,
            'queue' => $failedJob->queue,
            'failed_at' => $failedJob->failed_at,
            'exception_preview' => $failedJob->exception !== null
                ? mb_substr((string) $failedJob->exception, 0, 500)
                : null,
        ];
    }
}
