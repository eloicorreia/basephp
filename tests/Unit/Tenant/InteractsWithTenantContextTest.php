<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class InteractsWithTenantContextTest extends TestCase
{
    public function test_it_runs_callback_inside_tenant_execution_manager(): void
    {
        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main-'.str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main_'.str_replace('-', '', (string) Str::uuid()),
            'status' => 'active',
        ]);

        $job = new class($tenant->id)
        {
            use InteractsWithTenantContext;

            public function __construct(
                protected int|string $tenantId
            ) {}

            public function execute(): array
            {
                return $this->runInTenantContext(function (): array {
                    $tenantContext = app(TenantContext::class);
                    $currentTenant = $tenantContext->require();
                    $searchPathRow = DB::selectOne('SHOW search_path');

                    return [
                        'tenant_id' => $currentTenant->id,
                        'tenant_schema' => $currentTenant->schema_name,
                        'search_path' => is_object($searchPathRow) && isset($searchPathRow->search_path)
                            ? (string) $searchPathRow->search_path
                            : '',
                    ];
                });
            }
        };

        $tenantContext = new TenantContext;
        $tenantSearchPathService = new TenantSearchPathService;
        $executionManager = new TenantExecutionManager($tenantContext, $tenantSearchPathService);

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $tenantSearchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        $result = $job->execute();

        $this->assertSame($tenant->id, $result['tenant_id']);
        $this->assertSame($tenant->schema_name, $result['tenant_schema']);
        $this->assertStringContainsString($tenant->schema_name, $result['search_path']);
        $this->assertFalse($tenantContext->hasTenant());

        $resetRow = DB::selectOne('SHOW search_path');
        $resetSearchPath = is_object($resetRow) && isset($resetRow->search_path)
            ? (string) $resetRow->search_path
            : '';

        $this->assertSame('public', $resetSearchPath);
    }

    public function test_it_uses_the_expected_tenant_id_property_to_resolve_tenant_context(): void
    {
        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-property-'.str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant Property',
            'schema_name' => 'tenant_property_'.str_replace('-', '', (string) Str::uuid()),
            'status' => 'active',
        ]);

        $job = new class($tenant->id)
        {
            use InteractsWithTenantContext;

            protected int $tenantId;

            protected ?string $requestId = null;

            protected ?string $traceId = null;

            protected ?int $userId = null;

            protected ?int $oauthClientId = null;

            public function __construct(int $tenantId)
            {
                $this->initializeTenantContextData($tenantId);
            }
        };

        $this->assertSame($tenant->id, $job->getTenantId());
        $this->assertSame($tenant->id, $job->getTechnicalContext()['tenant_id']);
    }

    public function test_it_restores_context_after_execution_finishes(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Este teste requer PostgreSQL.');
        }

        $outer = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-outer-'.str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant Outer',
            'schema_name' => 'tenant_outer_'.str_replace('-', '', (string) Str::uuid()),
            'status' => 'active',
        ]);
        $inner = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-inner-'.str_replace('-', '', (string) Str::uuid()),
            'name' => 'Tenant Inner',
            'schema_name' => 'tenant_inner_'.str_replace('-', '', (string) Str::uuid()),
            'status' => 'active',
        ]);

        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $outer->schema_name));
        DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $inner->schema_name));

        $tenantContext = new TenantContext;
        $tenantSearchPathService = new TenantSearchPathService;
        $executionManager = new TenantExecutionManager($tenantContext, $tenantSearchPathService);
        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $tenantSearchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        $job = new class($inner->id)
        {
            use InteractsWithTenantContext;

            public function __construct(
                protected int|string $tenantId
            ) {}

            public function execute(): void
            {
                $this->runInTenantContext(static function (): void {});
            }
        };

        try {
            $executionManager->run($outer, function () use ($job, $outer, $tenantContext): void {
                $job->execute();

                $this->assertSame($outer->id, $tenantContext->require()->id);
            });

            $this->assertFalse($tenantContext->hasTenant());
        } finally {
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $outer->schema_name));
            DB::statement(sprintf('DROP SCHEMA IF EXISTS "%s" CASCADE', $inner->schema_name));
        }
    }
}
