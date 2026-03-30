<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Services\Tenant\TenantExecutionManager;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class SearchPathCleanupIntegrationTest extends TestCase
{
    use BuildsAuthTenancyFixtures;
    use RefreshDatabase;

    public function test_it_switches_to_tenant_schema_and_returns_to_public_after_execution(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->createTenant(
            code: 'tenant-search-path',
            status: 'active',
            schemaName: 'tenant_search_path_test'
        );

        DB::statement('CREATE SCHEMA IF NOT EXISTS "' . $tenant->schema_name . '"');

        /** @var TenantExecutionManager $executionManager */
        $executionManager = app(TenantExecutionManager::class);

        $schemaInsideExecution = $executionManager->run($tenant, function (): string {
            $currentSchema = DB::selectOne('select current_schema() as schema');

            return $currentSchema->schema;
        });

        $this->assertSame($tenant->schema_name, $schemaInsideExecution);

        $currentSchema = DB::selectOne('select current_schema() as schema');
        $this->assertSame('public', $currentSchema->schema);

        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);
        $this->assertNull($tenantContext->get());

        DB::statement('DROP SCHEMA IF EXISTS "' . $tenant->schema_name . '" CASCADE');
    }

    public function test_it_restores_previous_tenant_schema_when_execution_is_nested(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenantA = $this->createTenant(
            code: 'tenant-a',
            status: 'active',
            schemaName: 'tenant_nested_a'
        );

        $tenantB = $this->createTenant(
            code: 'tenant-b',
            status: 'active',
            schemaName: 'tenant_nested_b'
        );

        DB::statement('CREATE SCHEMA IF NOT EXISTS "' . $tenantA->schema_name . '"');
        DB::statement('CREATE SCHEMA IF NOT EXISTS "' . $tenantB->schema_name . '"');

        /** @var TenantExecutionManager $executionManager */
        $executionManager = app(TenantExecutionManager::class);

        $schemas = $executionManager->run($tenantA, function () use ($executionManager, $tenantB): array {
            $schemaInOuter = DB::selectOne('select current_schema() as schema')->schema;

            $schemaAfterInner = $executionManager->run($tenantB, function (): string {
                return DB::selectOne('select current_schema() as schema')->schema;
            });

            $schemaRestoredToOuter = DB::selectOne('select current_schema() as schema')->schema;

            return [
                'outer_before' => $schemaInOuter,
                'inner' => $schemaAfterInner,
                'outer_after' => $schemaRestoredToOuter,
            ];
        });

        $this->assertSame($tenantA->schema_name, $schemas['outer_before']);
        $this->assertSame($tenantB->schema_name, $schemas['inner']);
        $this->assertSame($tenantA->schema_name, $schemas['outer_after']);

        $currentSchema = DB::selectOne('select current_schema() as schema');
        $this->assertSame('public', $currentSchema->schema);

        DB::statement('DROP SCHEMA IF EXISTS "' . $tenantA->schema_name . '" CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS "' . $tenantB->schema_name . '" CASCADE');
    }
}