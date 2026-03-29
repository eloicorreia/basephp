<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Models\Tenant;
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
        $tenant = Tenant::query()->findOrFail($this->tenantId);

        /** @var TenantExecutionManager $executionManager */
        $executionManager = app(TenantExecutionManager::class);

        return $executionManager->run($tenant, $callback);
    }
}