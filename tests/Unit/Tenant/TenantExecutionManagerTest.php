<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use App\Services\Tenant\TenantSearchPathService;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TenantExecutionManagerTest extends TestCase
{
    public function test_it_sets_context_and_resets_to_public_after_callback(): void
    {
        $context = new TenantContext();

        /** @var TenantSearchPathService&MockInterface $searchPath */
        $searchPath = Mockery::mock(TenantSearchPathService::class);
        $searchPath->shouldReceive('setTenantSchema')
            ->once()
            ->with('tenant_main');
        $searchPath->shouldReceive('resetToPublic')
            ->once();

        $manager = new TenantExecutionManager($context, $searchPath);

        $tenant = new Tenant([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main',
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main',
            'status' => 'active',
        ]);

        $result = $manager->run($tenant, function () use ($context, $tenant): string {
            $this->assertSame($tenant, $context->get());

            return 'ok';
        });

        $this->assertSame('ok', $result);
        $this->assertNull($context->get());
    }

    public function test_it_restores_previous_tenant_when_execution_is_nested(): void
    {
        $context = new TenantContext();

        /** @var TenantSearchPathService&MockInterface $searchPath */
        $searchPath = Mockery::mock(TenantSearchPathService::class);
        $searchPath->shouldReceive('setTenantSchema')
            ->once()
            ->with('tenant_b');
        $searchPath->shouldReceive('setTenantSchema')
            ->once()
            ->with('tenant_a');
        $searchPath->shouldNotReceive('resetToPublic');

        $manager = new TenantExecutionManager($context, $searchPath);

        $tenantA = new Tenant([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-a',
            'name' => 'Tenant A',
            'schema_name' => 'tenant_a',
            'status' => 'active',
        ]);

        $tenantB = new Tenant([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-b',
            'name' => 'Tenant B',
            'schema_name' => 'tenant_b',
            'status' => 'active',
        ]);

        $context->set($tenantA);

        $manager->run($tenantB, function () use ($context, $tenantB): void {
            $this->assertSame($tenantB, $context->get());
        });

        $this->assertSame($tenantA, $context->get());
    }

    public function test_it_resets_context_even_when_callback_throws(): void
    {
        $context = new TenantContext();

        /** @var TenantSearchPathService&MockInterface $searchPath */
        $searchPath = Mockery::mock(TenantSearchPathService::class);
        $searchPath->shouldReceive('setTenantSchema')
            ->once()
            ->with('tenant_main');
        $searchPath->shouldReceive('resetToPublic')
            ->once();

        $manager = new TenantExecutionManager($context, $searchPath);

        $tenant = new Tenant([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main',
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main',
            'status' => 'active',
        ]);

        try {
            $manager->run($tenant, function (): void {
                throw new \RuntimeException('falha controlada');
            });

            $this->fail('Era esperada uma RuntimeException.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('falha controlada', $exception->getMessage());
        }

        $this->assertNull($context->get());
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}