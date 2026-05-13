<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Base\AbstractTenantAwareJob;

final class RebuildTenantCacheJob extends AbstractTenantAwareJob
{
    public function __construct(
        int $tenantId,
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
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $this->runInTenantContext(function (): void {
            // Regra tenant-aware aqui.
        });
    }
}
