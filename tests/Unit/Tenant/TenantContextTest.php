<?php

declare(strict_types=1);

namespace Tests\Unit\Tenant;

use App\Contracts\Multitenancy\TenantContextInterface;
use App\Exceptions\TenantContextNotDefinedException;
use App\Models\Tenant;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    public function test_it_starts_without_tenant(): void
    {
        $context = new TenantContext;

        $this->assertNull($context->get());
        $this->assertFalse($context->hasTenant());
    }

    public function test_it_sets_and_returns_current_tenant(): void
    {
        $context = new TenantContext;
        $tenant = new Tenant([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main',
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main',
            'status' => 'active',
        ]);

        $context->set($tenant);

        $this->assertTrue($context->hasTenant());
        $this->assertSame($tenant, $context->get());
        $this->assertSame($tenant, $context->require());
    }

    public function test_it_throws_when_require_is_called_without_tenant(): void
    {
        $context = new TenantContext;

        $this->expectException(TenantContextNotDefinedException::class);
        $this->expectExceptionMessage('Contexto de tenant não foi definido para a execução atual.');

        $context->require();
    }

    public function test_it_clears_current_tenant(): void
    {
        $context = new TenantContext;
        $tenant = new Tenant([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main',
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main',
            'status' => 'active',
        ]);

        $context->set($tenant);
        $context->clear();

        $this->assertNull($context->get());
        $this->assertFalse($context->hasTenant());
    }

    public function test_it_is_bound_as_scoped_context_in_container(): void
    {
        $tenant = new Tenant([
            'uuid' => (string) Str::uuid(),
            'code' => 'tenant-main',
            'name' => 'Tenant Main',
            'schema_name' => 'tenant_main',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        /** @var TenantContext $firstContext */
        $firstContext = app(TenantContext::class);
        $firstContext->set($tenant);

        $this->assertSame($firstContext, app(TenantContextInterface::class));
        $this->assertSame($tenant, app(TenantContext::class)->get());

        $this->app->forgetScopedInstances();

        /** @var TenantContext $secondContext */
        $secondContext = app(TenantContext::class);

        $this->assertNotSame($firstContext, $secondContext);
        $this->assertFalse($secondContext->hasTenant());
    }
}
