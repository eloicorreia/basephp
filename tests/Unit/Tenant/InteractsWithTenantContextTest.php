<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Jobs\Concerns\InteractsWithTenantContext;
use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class InteractsWithTenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_runs_callback_inside_tenant_execution_manager(): void
    {
        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main',
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

            public function execute(): string
            {
                return $this->runInTenantContext(function (): string {
                    return 'executado';
                });
            }
        };

        /** @var TenantExecutionManager&MockInterface $executionManager */
        $executionManager = Mockery::mock(TenantExecutionManager::class);

        $executionManager->shouldReceive('run')
            ->once()
            ->withArgs(function (Tenant $resolvedTenant, \Closure $callback) use ($tenant): bool {
                $this->assertSame($tenant->id, $resolvedTenant->id);

                return true;
            })
            ->andReturnUsing(function (Tenant $resolvedTenant, \Closure $callback): string {
                return $callback();
            });

        $this->app->instance(TenantExecutionManager::class, $executionManager);

        $this->assertSame('executado', $job->execute());
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}