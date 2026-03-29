<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Contracts\Multitenancy\TenantContextInterface;
use App\Models\Tenant;
use Closure;
use Throwable;

final class TenantExecutionManager
{
    public function __construct(
        private readonly TenantContextInterface $tenantContext,
        private readonly TenantSchemaManager $tenantSchemaManager,
    ) {
    }

    /**
     * @template TReturn
     *
     * @param Closure():TReturn $callback
     * @return TReturn
     */
    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previousTenant = $this->tenantContext->get();

        $this->tenantContext->set($tenant);
        $this->tenantSchemaManager->activate($tenant->schema_name);

        try {
            return $callback();
        } finally {
            if ($previousTenant !== null) {
                $this->tenantContext->set($previousTenant);
                $this->tenantSchemaManager->activate($previousTenant->schema_name);
            } else {
                $this->tenantContext->clear();
                $this->tenantSchemaManager->reset();
            }
        }
    }
}