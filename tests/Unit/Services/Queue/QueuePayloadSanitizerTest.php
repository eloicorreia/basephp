<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\QueuePayloadSanitizer;
use Tests\TestCase;

final class QueuePayloadSanitizerTest extends TestCase
{
    public function test_it_must_mask_sensitive_keys(): void
    {
        $service = new QueuePayloadSanitizer();

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
}