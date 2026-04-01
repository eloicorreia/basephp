<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use Tests\TestCase;

final class TenantAwareListenerFailureTest extends TestCase
{
    public function test_it_restores_public_schema_when_listener_execution_fails(): void
    {
        $this->markTestIncomplete('Implementar com listener de falha controlada.');
    }

    public function test_it_clears_tenant_context_when_listener_execution_fails(): void
    {
        $this->markTestIncomplete('Implementar com listener de falha controlada.');
    }

    public function test_it_restores_previous_outer_context_when_nested_listener_execution_fails(): void
    {
        $this->markTestIncomplete('Implementar com listener aninhado e falha controlada.');
    }
}
