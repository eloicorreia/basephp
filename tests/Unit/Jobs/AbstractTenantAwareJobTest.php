<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\Base\AbstractTenantAwareJob;
use Tests\TestCase;

final class AbstractTenantAwareJobTest extends TestCase
{
    public function test_it_must_store_technical_context(): void
    {
        $job = new class (
            10,
            'req-123',
            'trace-456',
            20,
            30
        ) extends AbstractTenantAwareJob {
            public function handle(): void
            {
            }
        };

        $this->assertSame(10, $job->getTenantId());
        $this->assertSame('req-123', $job->getRequestId());
        $this->assertSame('trace-456', $job->getTraceId());
        $this->assertSame(20, $job->getUserId());
        $this->assertSame(30, $job->getOauthClientId());
        $this->assertSame([
            'tenant_id' => 10,
            'request_id' => 'req-123',
            'trace_id' => 'trace-456',
            'user_id' => 20,
            'oauth_client_id' => 30,
        ], $job->getTechnicalContext());
    }
}