<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Contracts\Multitenancy\TenantResolverInterface;
use App\Services\Tenant\TenantExecutionManager;
use Closure;

trait InteractsWithTenantContext
{
    protected int|string $tenantId;

    /**
     * @template TReturn
     *
     * @param Closure():TReturn $callback
     * @return TReturn
     */
    protected function runInTenantContext(Closure $callback): mixed
    {
        /** @var TenantResolverInterface $resolver */
        $resolver = app(TenantResolverInterface::class);

        /** @var TenantExecutionManager $manager */
        $manager = app(TenantExecutionManager::class);

        $tenant = $resolver->resolveById($this->tenantId);

        return $manager->run($tenant, $callback);
    }
}