<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantAwareJobFailureTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_it_restores_public_schema_when_job_execution_fails(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->createTenant(
            code: 'tfj-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            status: 'active',
            schemaName: 'tfj_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));

        $tenantContext = new TenantContext();
        $searchPathService = new TenantSearchPathService();
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Falha controlada no job tenant-aware.');

            (new FailingTenantAwareJob($tenant->id))->handle();
        } finally {
            $row = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($row);
            $this->assertSame('public', $row->schema);

            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

    public function test_it_clears_tenant_context_when_job_execution_fails(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->createTenant(
            code: 'tfjc-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            status: 'active',
            schemaName: 'tfjc_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $tenant->schema_name));

        $tenantContext = new TenantContext();
        $searchPathService = new TenantSearchPathService();
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        try {
            try {
                (new FailingTenantAwareJob($tenant->id))->handle();
                $this->fail('O job deveria lançar uma exceção controlada.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Falha controlada no job tenant-aware.', $exception->getMessage());
            }

            $this->assertNull($tenantContext->get());
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

    public function test_it_restores_previous_outer_context_when_nested_job_execution_fails(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $outerTenant = $this->createTenant(
            code: 'tofj-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            status: 'active',
            schemaName: 'tofj_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        $innerTenant = $this->createTenant(
            code: 'tifj-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            status: 'active',
            schemaName: 'tifj_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $outerTenant->schema_name));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $innerTenant->schema_name));

        $tenantContext = new TenantContext();
        $searchPathService = new TenantSearchPathService();
        $executionManager = new TenantExecutionManager($tenantContext, $searchPathService);

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $searchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        try {
            $executionManager->run($outerTenant, function () use ($tenantContext, $outerTenant, $innerTenant): void {
                try {
                    (new FailingTenantAwareJob($innerTenant->id))->handle();
                    $this->fail('O job interno deveria lançar uma exceção controlada.');
                } catch (RuntimeException $exception) {
                    $this->assertSame('Falha controlada no job tenant-aware.', $exception->getMessage());
                }

                $this->assertSame($outerTenant->id, $tenantContext->require()->id);

                $row = DB::selectOne('select current_schema() as schema');
                $this->assertNotNull($row);
                $this->assertSame($outerTenant->schema_name, $row->schema);
            });

            $this->assertNull($tenantContext->get());
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $outerTenant->schema_name));
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $innerTenant->schema_name));
        }
    }
}

final class FailingTenantAwareJob implements ShouldQueue
{
    use InteractsWithTenantContext;

    public function __construct(int $tenantId)
    {
        $this->initializeTenantContextData($tenantId);
    }

    public function handle(): void
    {
        $this->runInTenantContext(function (): never {
            throw new RuntimeException('Falha controlada no job tenant-aware.');
        });
    }
}
