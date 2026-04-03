<?php

declare(strict_types=1);

namespace App\Services\Queue;

final class QueuePayloadSanitizer
{
    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'senha',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'client_secret',
        'secret',
        'api_key',
        'apikey',
    ];

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        return $this->sanitizeValue($payload);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $key => $item) {
                if (is_string($key) && $this->isSensitiveKey($key)) {
                    $sanitized[$key] = '[REDACTED]';

                    continue;
                }

                $sanitized[$key] = $this->sanitizeValue($item);
            }

            return $sanitized;
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array(mb_strtolower($key), self::SENSITIVE_KEYS, true);
    }
}