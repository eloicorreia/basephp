<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QueueSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'queue' => $this['queue'],
            'pending_jobs' => $this['pending_jobs'],
            'running_jobs' => $this['running_jobs'],
            'failed_jobs' => $this['failed_jobs'],
            'generated_at' => $this['generated_at'],
        ];
    }
}
