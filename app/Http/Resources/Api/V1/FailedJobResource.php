<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FailedJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'connection' => $this->connection,
            'queue' => $this->queue,
            'failed_at' => $this->failed_at,
            'exception_preview' => $this->exception !== null
                ? mb_substr($this->exception, 0, 500)
                : null,
        ];
    }
}