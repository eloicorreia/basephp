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
    public function sanitizePayload(?array $payload, ?int $maxStringLength = null): array
    {
        $sanitized = $this->sensitiveDataSanitizer->sanitizeArray($payload) ?? [];

        return $this->limitArray(
            payload: $sanitized,
            maxStringLength: $this->textLimit($maxStringLength),
            maxItems: max(1, (int) config('admin_web.logs.max_payload_items', 100)),
        );
    }

    public function sanitizeText(?string $text, ?int $maxLength = null): ?string
    {
        if ($text === null) {
            return null;
        }

        return $this->sensitiveDataSanitizer->sanitizeText(
            value: $text,
            maxLength: $this->textLimit($maxLength),
        );
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private function limitArray(array $payload, int $maxStringLength, int $maxItems): array
    {
        $limited = [];
        $index = 0;

        foreach ($payload as $key => $value) {
            if ($index >= $maxItems) {
                $limited['_truncated'] = 'Max visual item count reached.';

                break;
            }

            $limited[$key] = $this->limitValue($value, $maxStringLength, $maxItems);
            $index++;
        }

        return $limited;
    }

    private function limitValue(mixed $value, int $maxStringLength, int $maxItems): mixed
    {
        if (is_array($value)) {
            return $this->limitArray($value, $maxStringLength, $maxItems);
        }

        if (is_string($value)) {
            return $this->limitString($value, $maxStringLength);
        }

        return $value;
    }

    private function limitString(string $value, int $maxStringLength): string
    {
        if (mb_strlen($value) <= $maxStringLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxStringLength).'...[truncated]';
    }

    private function textLimit(?int $maxLength): int
    {
        return max(100, $maxLength ?? (int) config('admin_web.logs.preview_text_limit', 1000));
    }
}
