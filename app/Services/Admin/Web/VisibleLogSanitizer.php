<?php

declare(strict_types=1);

namespace App\Services\Admin\Web;

use App\Support\Logging\SensitiveDataSanitizer;

final readonly class VisibleLogSanitizer
{
    public function __construct(
        private SensitiveDataSanitizer $sensitiveDataSanitizer
    ) {}

    /**
     * @param  array<mixed>|null  $payload
     * @return array<mixed>
     */
    public function sanitizePayload(?array $payload): array
    {
        return $this->sensitiveDataSanitizer->sanitizeArray($payload) ?? [];
    }

    public function sanitizeText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return $this->sensitiveDataSanitizer->sanitizeText($text);
    }
}
