<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use Tests\TestCase;

final class TenantAwareJobFailureTest extends TestCase
{
    public function test_it_restores_public_schema_when_job_execution_fails(): void
    {
        $this->markTestIncomplete('Implementar com job de falha controlada.');
    }

    public function test_it_clears_tenant_context_when_job_execution_fails(): void
    {
        $this->markTestIncomplete('Implementar com job de falha controlada.');
    }

    public function test_it_restores_previous_outer_context_when_nested_job_execution_fails(): void
    {
        $this->markTestIncomplete('Implementar com job aninhado e falha controlada.');
    }
}
