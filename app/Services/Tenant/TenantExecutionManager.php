<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Support\Tenant\TenantContext;
use Closure;

final class TenantExecutionManager
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantSearchPathService $tenantSearchPathService,
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
        $this->tenantSearchPathService->setTenantSchema($tenant->schema_name);

        try {
            return $callback();
        } finally {
            if ($previousTenant !== null) {
                $this->tenantContext->set($previousTenant);
                $this->tenantSearchPathService->setTenantSchema($previousTenant->schema_name);

                return;
            }

            $this->tenantContext->clear();
            $this->tenantSearchPathService->resetToPublic();
        }
    }
}