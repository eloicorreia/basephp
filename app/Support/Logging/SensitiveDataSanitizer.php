<?php

declare(strict_types=1);

namespace App\Support\Logging;

final class SensitiveDataSanitizer
{
    private const MASK = '***';

    private const DEFAULT_MAX_DEPTH = 8;

    private const MAX_ARRAY_ITEMS = 200;

    private const MAX_STRING_LENGTH = 4000;

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
        'token_type',
        'authorization',
        'bearer_token',
        'command',
        'secret',
        'api_key',
        'x_api_key',
        'apikey',
        'private_key',
        'public_key',
        'cookie',
        'set_cookie',
        'session',
        'csrf_token',
        'xsrf_token',
        'signature',
        'credential',
        'credentials',
        'password_encrypted',
    ];

    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEY_FRAGMENTS = [
        'password',
        'passwd',
        'pwd',
        'senha',
        'secret',
        'token',
        'authorization',
        'api_key',
        'private_key',
        'cookie',
        'session',
        'csrf',
        'xsrf',
        'signature',
        'credential',
    ];

    private const SENSITIVE_TEXT_KEY_PATTERN = 'password|passwd|pwd|senha|client_secret|clientSecret|access_token|accessToken|refresh_token|refreshToken|api_key|apiKey|apikey|x-api-key|private_key|privateKey|secret|token|authorization|cookie|set-cookie|session|csrf_token|xsrf_token|signature|credential|credentials';

    /**
     * @param  array<mixed>|null  $data
     * @return array<mixed>|null
     */
    public function sanitizeArray(?array $data, string $mask = self::MASK): ?array
    {
        if ($data === null) {
            return null;
        }

        return $this->sanitizeArrayAtDepth($data, $mask, 0);
    }

    public function sanitizeText(
        string $value,
        string $mask = self::MASK,
        int $maxLength = self::MAX_STRING_LENGTH
    ): string {
        $sanitized = $this->maskSensitiveText($value, $mask);

        if (mb_strlen($sanitized) <= $maxLength) {
            return $sanitized;
        }

        return mb_substr($sanitized, 0, $maxLength).'...[truncated]';
    }

    public function isSensitiveKey(string $key): bool
    {
        $normalizedKey = $this->normalizeKey($key);

        if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
            return true;
        }

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalizedKey, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function sanitizeArrayAtDepth(array $data, string $mask, int $depth): array
    {
        if ($depth >= self::DEFAULT_MAX_DEPTH) {
            return ['_truncated' => 'Max sanitization depth reached.'];
        }

        $sanitized = [];
        $index = 0;

        foreach ($data as $key => $value) {
            if ($index >= self::MAX_ARRAY_ITEMS) {
                $sanitized['_truncated'] = 'Max sanitization item count reached.';

                break;
            }

            $index++;

            if (is_string($key) && $this->isSensitiveKey($key)) {
                $sanitized[$key] = $mask;

                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($value, $mask, $depth + 1);
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value, string $mask, int $depth): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArrayAtDepth($value, $mask, $depth);
        }

        if (is_string($value)) {
            return $this->sanitizeText($value, $mask);
        }

        return $value;
    }

    private function normalizeKey(string $key): string
    {
        $snakeKey = (string) preg_replace('/(?<!^)[A-Z]/', '_$0', $key);
        $normalizedKey = mb_strtolower($snakeKey);
        $normalizedKey = (string) preg_replace('/[^a-z0-9]+/', '_', $normalizedKey);

        return trim($normalizedKey, '_');
    }

    private function maskSensitiveText(string $value, string $mask): string
    {
        $sanitized = $value;
        $authorizationPattern = '/\b(?P<key>authorization)\b(?P<separator>\s*[:=]\s*)Bearer\s+[A-Za-z0-9._~+\/=-]+/iu';
        $sanitized = (string) preg_replace_callback(
            $authorizationPattern,
            static fn (array $matches): string => $matches['key'].$matches['separator'].$mask,
            $sanitized
        );

        $jsonPattern = '/(["\'])(?P<key>'.self::SENSITIVE_TEXT_KEY_PATTERN.')\1\s*:\s*(["\'])(?:\\\\.|(?!\3).)*\3/iu';
        $sanitized = (string) preg_replace_callback(
            $jsonPattern,
            static fn (array $matches): string => $matches[1].$matches['key'].$matches[1].':'.$matches[3].$mask.$matches[3],
            $sanitized
        );

        $assignmentPattern = '/\b(?P<key>'.self::SENSITIVE_TEXT_KEY_PATTERN.')\b(?P<separator>\s*[:=]\s*)(?P<value>"[^"]*"|\'[^\']*\'|[^\s,;&]+)/iu';
        $sanitized = (string) preg_replace_callback(
            $assignmentPattern,
            static fn (array $matches): string => $matches['key'].$matches['separator'].$mask,
            $sanitized
        );

        return (string) preg_replace(
            '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i',
            'Bearer '.$mask,
            $sanitized
        );
    }
}
