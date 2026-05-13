<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\QueuePayloadSanitizer;
use App\Support\Logging\SensitiveDataSanitizer;
use Tests\TestCase;

final class QueuePayloadSanitizerTest extends TestCase
{
    public function test_it_must_mask_sensitive_keys(): void
    {
        $service = new QueuePayloadSanitizer(new SensitiveDataSanitizer());

        $sanitized = $service->sanitize([
            'email' => 'user@example.com',
            'password' => '123456',
            'nested' => [
                'token' => 'secret-token',
                'safe' => 'ok',
            ],
        ]);

        $this->assertSame('user@example.com', $sanitized['email']);
        $this->assertSame('[REDACTED]', $sanitized['password']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['token']);
        $this->assertSame('ok', $sanitized['nested']['safe']);
    }

    public function test_it_must_mask_sensitive_keys_recursively_with_shared_key_list(): void
    {
        $service = new QueuePayloadSanitizer(new SensitiveDataSanitizer());

        $sanitized = $service->sanitize([
            'payload' => [
                'authorization' => 'Bearer secret',
                'mail' => [
                    'password_encrypted' => 'encrypted-secret',
                    'api_key' => 'key-secret',
                    'subject' => 'safe',
                ],
            ],
        ]);

        $this->assertSame('[REDACTED]', $sanitized['payload']['authorization']);
        $this->assertSame('[REDACTED]', $sanitized['payload']['mail']['password_encrypted']);
        $this->assertSame('[REDACTED]', $sanitized['payload']['mail']['api_key']);
        $this->assertSame('safe', $sanitized['payload']['mail']['subject']);
    }
}
