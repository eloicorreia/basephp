<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Logging;

use App\Support\Logging\SensitiveDataSanitizer;
use Tests\TestCase;

final class SensitiveDataSanitizerTest extends TestCase
{
    public function test_it_masks_sensitive_key_variants(): void
    {
        $sanitizer = new SensitiveDataSanitizer;

        $sanitized = $sanitizer->sanitizeArray([
            'clientSecret' => 'secret',
            'X-Api-Key' => 'api-key',
            'private_key' => 'private-key',
            'headers' => [
                'set-cookie' => ['session=abc'],
            ],
            'safe' => 'visible',
        ]);

        $this->assertSame('***', $sanitized['clientSecret']);
        $this->assertSame('***', $sanitized['X-Api-Key']);
        $this->assertSame('***', $sanitized['private_key']);
        $this->assertSame('***', $sanitized['headers']['set-cookie']);
        $this->assertSame('visible', $sanitized['safe']);
    }

    public function test_it_masks_sensitive_values_embedded_in_text(): void
    {
        $sanitizer = new SensitiveDataSanitizer;

        $sanitized = $sanitizer->sanitizeText(
            'Authorization: Bearer real-token password=secret clientSecret: "client-secret" {"api_key":"api-secret"}'
        );

        $this->assertStringNotContainsString('real-token', $sanitized);
        $this->assertStringNotContainsString('password=secret', $sanitized);
        $this->assertStringNotContainsString('client-secret', $sanitized);
        $this->assertStringNotContainsString('api-secret', $sanitized);
        $this->assertStringContainsString('Authorization: ***', $sanitized);
        $this->assertStringContainsString('password=***', $sanitized);
    }

    public function test_it_truncates_deep_and_large_payloads(): void
    {
        $sanitizer = new SensitiveDataSanitizer;

        $nested = ['level' => 'leaf'];

        for ($i = 0; $i < 10; $i++) {
            $nested = ['next' => $nested];
        }

        $sanitized = $sanitizer->sanitizeArray($nested);

        $this->assertArrayHasKey('_truncated', $sanitized['next']['next']['next']['next']['next']['next']['next']['next']);

        $longText = str_repeat('a', 4100);

        $this->assertStringEndsWith('...[truncated]', $sanitizer->sanitizeText($longText));
    }
}
