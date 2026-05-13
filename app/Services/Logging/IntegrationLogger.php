<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Models\Tenant\IntegrationLog;
use App\Support\Logging\SensitiveDataSanitizer;

class IntegrationLogger
{
    public function __construct(
        private readonly SensitiveDataSanitizer $sensitiveDataSanitizer,
    ) {}

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function logRequest(
        string $service,
        string $operation,
        ?string $destination = null,
        ?array $payload = null,
    ): IntegrationLog {
        return IntegrationLog::query()->create([
            'request_id' => request()->attributes->get('request_id'),
            'trace_id' => request()->attributes->get('trace_id'),
            'system_name' => $service,
            'direction' => 'outbound',
            'operation' => $operation,
            'endpoint' => $destination,
            'request_payload' => $this->sensitiveDataSanitizer->sanitizeArray($payload),
            'processing_status' => 'queued',
            'message' => 'Integration request queued.',
            'created_at' => now(),
        ]);
    }
}
