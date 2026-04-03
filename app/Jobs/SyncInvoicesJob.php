<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Base\AbstractTenantAwareJob;
use App\Services\Billing\InvoiceSyncService;

final class SyncInvoicesJob extends AbstractTenantAwareJob
{
    /**
     * Integrações costumam precisar de mais tolerância a falhas transitórias.
     */
    public int $tries = 5;

    /**
     * Sincronizações externas podem levar mais tempo.
     */
    public int $timeout = 300;

    public int $maxExceptions = 3;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        int $tenantId,
        private readonly array $payload,
        ?string $requestId = null,
        ?string $traceId = null,
        ?int $userId = null,
        ?int $oauthClientId = null
    ) {
        parent::__construct(
            tenantId: $tenantId,
            requestId: $requestId,
            traceId: $traceId,
            userId: $userId,
            oauthClientId: $oauthClientId
        );

        $this->onConnection('database');
        $this->onQueue('integrations');
    }

    /**
     * Retry progressivo para falhas transitórias.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300, 600];
    }

    public function handle(InvoiceSyncService $service): void
    {
        $this->runInTenantContext(function () use ($service): void {
            $service->sync($this->payload);
        });
    }
}