<?php

declare(strict_types=1);

namespace App\Services\Queue;

use App\Support\Logging\SensitiveDataSanitizer;

final class QueuePayloadSanitizer
{
    public function __construct(
        private readonly SensitiveDataSanitizer $sensitiveDataSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        return $this->sensitiveDataSanitizer->sanitizeArray($payload, '[REDACTED]') ?? [];
    }
}
