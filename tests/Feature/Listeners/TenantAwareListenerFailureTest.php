<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\BuildsAuthTenancyFixtures;
use Tests\TestCase;

final class TenantAwareListenerFailureTest extends TestCase
{
    use BuildsAuthTenancyFixtures;

    public function test_it_restores_public_schema_when_listener_execution_fails(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->createTenant(
            code: 'tfl-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            status: 'active',
            schemaName: 'tfl_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
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
            $this->expectExceptionMessage('Falha controlada no listener tenant-aware.');

            (new FailingTenantAwareQueuedListener())->handle(new FailingTenantAwareEvent($tenant->id));
        } finally {
            $row = DB::selectOne('select current_schema() as schema');
            $this->assertNotNull($row);
            $this->assertSame('public', $row->schema);

            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

    public function test_it_clears_tenant_context_when_listener_execution_fails(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $tenant = $this->createTenant(
            code: 'tflc-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            status: 'active',
            schemaName: 'tflc_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
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
                (new FailingTenantAwareQueuedListener())->handle(new FailingTenantAwareEvent($tenant->id));
                $this->fail('O listener deveria lançar uma exceção controlada.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Falha controlada no listener tenant-aware.', $exception->getMessage());
            }

            $this->assertNull($tenantContext->get());
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $tenant->schema_name));
        }
    }

    public function test_it_restores_previous_outer_context_when_nested_listener_execution_fails(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $outerTenant = $this->createTenant(
            code: 'tofl-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            status: 'active',
            schemaName: 'tofl_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
        );

        $innerTenant = $this->createTenant(
            code: 'tifl-' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12),
            status: 'active',
            schemaName: 'tifl_' . substr(str_replace('-', '', (string) Str::uuid()), 0, 12)
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
                    (new FailingTenantAwareQueuedListener())->handle(new FailingTenantAwareEvent($innerTenant->id));
                    $this->fail('O listener interno deveria lançar uma exceção controlada.');
                } catch (RuntimeException $exception) {
                    $this->assertSame('Falha controlada no listener tenant-aware.', $exception->getMessage());
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

final readonly class FailingTenantAwareEvent
{
    public function __construct(public int $tenantId)
    {
    }
}

final class FailingTenantAwareQueuedListener implements ShouldQueue
{
    public function handle(FailingTenantAwareEvent $event): void
    {
        /** @var TenantExecutionManager $executionManager */
        $executionManager = app(TenantExecutionManager::class);

        $tenant = Tenant::query()->findOrFail($event->tenantId);

        $executionManager->run($tenant, function (): never {
            throw new RuntimeException('Falha controlada no listener tenant-aware.');
        });
    }
}
