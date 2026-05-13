<?php

declare(strict_types=1);

namespace App\Support\Logging;

final class SensitiveDataSanitizer
{
    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'new_password_confirmation',
        'senha',
        'client_secret',
        'access_token',
        'refresh_token',
        'token',
        'authorization',
        'bearer_token',
        'command',
        'secret',
        'api_key',
        'apikey',
        'password_encrypted',
    ];

    /**
     * @param array<mixed>|null $data
     * @return array<mixed>|null
     */
    public function sanitizeArray(?array $data, string $mask = '***'): ?array
    {
        if ($data === null) {
            return null;
        }

        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $sanitized[$key] = $mask;
                continue;
            }

            $sanitized[$key] = is_array($value)
                ? $this->sanitizeArray($value, $mask)
                : $value;
        }

        return $sanitized;
    }

    public function isSensitiveKey(string $key): bool
    {
        return in_array(mb_strtolower($key), self::SENSITIVE_KEYS, true);
    }
}
