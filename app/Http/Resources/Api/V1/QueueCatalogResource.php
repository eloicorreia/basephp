<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class QueueCatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this['name'],
            'purpose' => $this['purpose'],
            'retry_limit' => $this['retry_limit'],
            'timeout_seconds' => $this['timeout_seconds'],
        ];
    }
}