<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InteractsWithTenantContextTest extends TestCase
{

    public function test_it_runs_callback_inside_tenant_execution_manager(): void
    {
        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main-' . str_replace('-', '', (string) \Illuminate\Support\Str::uuid()),
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main',
            'status' => 'active',
        ]);

        $job = new class ($tenant->id) {
            use InteractsWithTenantContext;

            public function __construct(int $tenantId)
            {
                $this->tenantId = $tenantId;
            }

            public function execute(): array
            {
                return $this->runInTenantContext(function (): array {
                    /** @var TenantContext $tenantContext */
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

        $tenantContext = new TenantContext();
        $tenantSearchPathService = new TenantSearchPathService();
        $executionManager = new TenantExecutionManager(
            $tenantContext,
            $tenantSearchPathService,
        );

        $this->app->instance(TenantContext::class, $tenantContext);
        $this->app->instance(TenantSearchPathService::class, $tenantSearchPathService);
        $this->app->instance(TenantExecutionManager::class, $executionManager);

        $result = $job->execute();

        $this->assertSame($tenant->id, $result['tenant_id']);
        $this->assertSame('tenant_main', $result['tenant_schema']);
        $this->assertStringContainsString('tenant_main', $result['search_path']);

        $this->assertFalse($tenantContext->hasTenant());

        $resetRow = DB::selectOne('SHOW search_path');
        $resetSearchPath = is_object($resetRow) && isset($resetRow->search_path)
            ? (string) $resetRow->search_path
            : '';

        $this->assertSame('public', $resetSearchPath);
    }
}