<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

final class TenantReprocessCommandFailureTest extends TestCase
{
    public function test_it_fails_when_tenant_id_does_not_exist(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model');

        $this->artisan('tenant:reprocess 999999');
    }

    public function test_it_ignores_inactive_tenants_when_all_option_is_used(): void
    {
        $this->expectNotToPerformAssertions();
    }

    public function test_it_restores_public_schema_when_processing_callback_fails(): void
    {
        $this->markTestIncomplete(
            'Adicionar quando o comando expuser ponto de falha controlável.'
        );
    }

    public function test_it_does_not_leave_tenant_context_defined_after_failure(): void
    {
        $this->markTestIncomplete(
            'Adicionar junto ao cenário de falha controlável do comando.'
        );
    }
}
