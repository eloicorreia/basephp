<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Exceptions\TenantNotAvailableForAsyncExecutionException;
use App\Models\Tenant;
use App\Services\Tenant\TenantExecutionManager;
use Closure;

trait InteractsWithTenantContext
{
    protected function initializeTenantContextData(
        int $tenantId,
        ?string $requestId = null,
        ?string $traceId = null,
        ?int $userId = null,
        ?int $oauthClientId = null
    ): void {
        $this->tenantId = $tenantId;
        $this->requestId = $requestId;
        $this->traceId = $traceId;
        $this->userId = $userId;
        $this->oauthClientId = $oauthClientId;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getOauthClientId(): ?int
    {
        return $this->oauthClientId;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function getTechnicalContext(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'request_id' => $this->requestId,
            'trace_id' => $this->traceId,
            'user_id' => $this->userId,
            'oauth_client_id' => $this->oauthClientId,
        ];
    }

    protected function runInTenantContext(Closure $callback): mixed
    {
        $tenant = Tenant::query()
            ->whereKey($this->tenantId)
            ->where('status', 'active')
            ->first();

        if ($tenant === null) {
            throw TenantNotAvailableForAsyncExecutionException::forTenantId(
                $this->tenantId
            );
        }

        /** @var TenantExecutionManager $tenantExecutionManager */
        $tenantExecutionManager = app(TenantExecutionManager::class);

        return $tenantExecutionManager->run($tenant, $callback);
    }
}