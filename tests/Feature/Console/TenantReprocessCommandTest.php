<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TenantReprocessCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fails_when_no_tenant_id_and_no_all_option_are_provided(): void
    {
        $this->artisan('tenant:reprocess')
            ->expectsOutput('Informe {tenant_id} ou utilize --all.')
            ->assertFailed();
    }

    public function test_it_processes_only_active_tenants_when_all_option_is_used(): void
    {
        $activeA = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-a',
            'name' => 'Tenant A',
            'schema_name' => 'tenant_a',
            'status' => 'active',
        ]);

        $inactive = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-b',
            'name' => 'Tenant B',
            'schema_name' => 'tenant_b',
            'status' => 'inactive',
        ]);

        $activeC = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-c',
            'name' => 'Tenant C',
            'schema_name' => 'tenant_c',
            'status' => 'active',
        ]);

        /** @var TenantExecutionManager&MockInterface $executionManager */
        $executionManager = Mockery::mock(TenantExecutionManager::class);

        $executionManager->shouldReceive('run')
            ->once()
            ->withArgs(function (Tenant $tenant, \Closure $callback) use ($activeA): bool {
                $this->assertSame($activeA->id, $tenant->id);

                return true;
            })
            ->andReturnUsing(function (Tenant $tenant, \Closure $callback): void {
                $callback();
            });

        $executionManager->shouldReceive('run')
            ->once()
            ->withArgs(function (Tenant $tenant, \Closure $callback) use ($activeC): bool {
                $this->assertSame($activeC->id, $tenant->id);

                return true;
            })
            ->andReturnUsing(function (Tenant $tenant, \Closure $callback): void {
                $callback();
            });

        $this->app->instance(TenantExecutionManager::class, $executionManager);

        $this->artisan('tenant:reprocess --all')
            ->expectsOutput("Processando tenant {$activeA->id} no schema {$activeA->schema_name}")
            ->expectsOutput("Processando tenant {$activeC->id} no schema {$activeC->schema_name}")
            ->assertSuccessful();

        $this->assertDatabaseHas('tenants', [
            'id' => $inactive->id,
            'status' => 'inactive',
        ]);
    }

    public function test_it_processes_a_single_tenant_when_tenant_id_is_provided(): void
    {
        $tenant = Tenant::query()->create([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main',
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main',
            'status' => 'active',
        ]);

        /** @var TenantExecutionManager&MockInterface $executionManager */
        $executionManager = Mockery::mock(TenantExecutionManager::class);

        $executionManager->shouldReceive('run')
            ->once()
            ->withArgs(function (Tenant $resolvedTenant, \Closure $callback) use ($tenant): bool {
                $this->assertSame($tenant->id, $resolvedTenant->id);

                return true;
            })
            ->andReturnUsing(function (Tenant $resolvedTenant, \Closure $callback): void {
                $callback();
            });

        $this->app->instance(TenantExecutionManager::class, $executionManager);

        $this->artisan('tenant:reprocess ' . $tenant->id)
            ->expectsOutput("Processando tenant {$tenant->id} no schema {$tenant->schema_name}")
            ->assertSuccessful();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}